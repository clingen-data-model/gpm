<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Modules\Group\Models\Group as GroupModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;

#[Group('groups')]
#[Group('members')]
class MemberCandidatesListTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdpClient $client;
    private FakeIdpStore $store;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->client = app(FakeIdpClient::class);
        $this->store = $this->client->store();

        $this->user = $this->setupUser();
        $this->user->person()->create(Person::factory()->make(['first_name' => 'Cheryl', 'last_name' => 'Tunt'])->toArray());
        $this->group = GroupModel::factory()->create();
        $this->userMember = MemberAdd::run($this->group, $this->user->person);

        $this->url = 'api/groups/'.$this->group->uuid.'/members/candidates';
    }

    private function actingAsCoordinator(): void
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );
        Sanctum::actingAs($this->user->fresh());
    }

    private function candidates(array $query)
    {
        return $this->getJson($this->url.'?'.http_build_query($query));
    }

    #[Test]
    public function guests_and_unprivileged_members_cannot_search()
    {
        $this->candidates(['last_name' => 'Tunt'])->assertStatus(401);

        Sanctum::actingAs($this->user);
        $this->candidates(['last_name' => 'Tunt'])->assertStatus(403);
    }

    #[Test]
    public function lists_gpm_people_with_account_and_membership_flags()
    {
        $this->actingAsCoordinator();
        $pam = Person::factory()->create(['first_name' => 'Pam', 'last_name' => 'Tuntley', 'email' => 'pam@example.com', 'user_id' => null]);

        $response = $this->candidates(['last_name' => 'tunt'])->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'kind' => 'person',
            'person_id' => $this->user->person->id,
            'has_account' => true,
            'already_member' => true,
            'has_idp_identity' => false,
        ]);
        $response->assertJsonFragment([
            'kind' => 'person',
            'person_id' => $pam->id,
            'email' => 'pam@example.com',
            'has_account' => false,
            'already_member' => false,
        ]);
        $response->assertJsonPath('idp_enabled', true)->assertJsonPath('idp_available', true);
    }

    #[Test]
    public function filters_gpm_people_by_email_substring()
    {
        $this->actingAsCoordinator();
        Person::factory()->create(['email' => 'krieger@isis.example', 'user_id' => null]);
        Person::factory()->create(['email' => 'archer@isis.example', 'user_id' => null]);

        $this->candidates(['email' => 'krieger@'])->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'krieger@isis.example');
    }

    #[Test]
    public function lists_one_row_per_address_of_an_identity_not_in_the_gpm()
    {
        $this->actingAsCoordinator();
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'emails' => ['z.zardoz@other.org'], 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $response = $this->candidates(['last_name' => 'Zar'])->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0', [
            'kind' => 'idp', 'person_id' => null, 'uuid' => null, 'first_name' => 'Zed', 'last_name' => 'Zardoz',
            'name' => 'Zed Zardoz', 'email' => 'zed@example.com', 'institution' => null, 'has_account' => false,
            'has_idp_identity' => true, 'idp_id' => 'user_fake_zed', 'already_member' => false,
        ]);
        $response->assertJsonPath('data.1.email', 'z.zardoz@other.org');
    }

    #[Test]
    public function narrows_identities_by_the_other_typed_fields()
    {
        $this->actingAsCoordinator();
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'first_name' => 'Zed', 'last_name' => 'Zardoz']);
        $this->store->put(['id' => 'user_fake_zoe', 'email' => 'zoe@example.com', 'first_name' => 'Zoe', 'last_name' => 'Zardoz']);

        $this->candidates(['last_name' => 'Zardoz', 'first_name' => 'zo'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.idp_id', 'user_fake_zoe');
    }

    #[Test]
    public function drops_identities_already_linked_to_a_user()
    {
        $this->actingAsCoordinator();
        User::factory()->linkedToIdp('user_fake_zed')->create(['email' => 'unrelated@example.com']);
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $this->candidates(['last_name' => 'Zardoz'])->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function drops_identities_whose_address_belongs_to_a_user()
    {
        $this->actingAsCoordinator();
        User::factory()->create(['email' => 'Z.Zardoz@other.org']);
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'emails' => ['z.zardoz@other.org'], 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $this->candidates(['last_name' => 'Zardoz'])->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function folds_an_identity_into_a_matching_person_without_an_account()
    {
        $this->actingAsCoordinator();
        $person = Person::factory()->create(['first_name' => 'Zed', 'last_name' => 'Zardoz', 'email' => 'Zed@Example.com', 'user_id' => null]);
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'emails' => ['z.zardoz@other.org'], 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $response = $this->candidates(['last_name' => 'Zardoz'])->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'kind' => 'person',
            'person_id' => $person->id,
            'has_account' => false,
            'has_idp_identity' => true,
            'idp_id' => 'user_fake_zed',
        ]);
    }

    #[Test]
    public function folds_into_a_person_the_gpm_filter_did_not_return()
    {
        $this->actingAsCoordinator();
        $person = Person::factory()->create(['first_name' => 'Zed', 'last_name' => 'Smith', 'email' => 'z.zardoz@other.org', 'user_id' => null]);
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'emails' => ['z.zardoz@other.org'], 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $response = $this->candidates(['last_name' => 'Zardoz'])->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.person_id', $person->id)->assertJsonPath('data.0.idp_id', 'user_fake_zed');
    }

    #[Test]
    public function does_not_search_the_directory_below_the_minimum_length()
    {
        $this->actingAsCoordinator();
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $this->candidates(['last_name' => 'Za'])->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('idp_available', true);

        $this->assertSame([], $this->client->calls());
    }

    #[Test]
    public function degrades_to_gpm_results_when_the_directory_is_unreachable()
    {
        $this->actingAsCoordinator();
        Person::factory()->create(['last_name' => 'Zardoz', 'user_id' => null]);
        $this->client->failNext();

        $response = $this->candidates(['last_name' => 'Zardoz'])->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('idp_available', false)->assertJsonPath('idp_enabled', true);
    }

    #[Test]
    public function returns_gpm_results_only_when_the_idp_driver_is_null()
    {
        config(['idp.driver' => 'null']);
        $this->actingAsCoordinator();
        Person::factory()->create(['last_name' => 'Zardoz', 'user_id' => null]);
        $this->store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'first_name' => 'Zed', 'last_name' => 'Zardoz']);

        $response = $this->candidates(['last_name' => 'Zardoz'])->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.kind', 'person')->assertJsonPath('idp_enabled', false);
    }
}
