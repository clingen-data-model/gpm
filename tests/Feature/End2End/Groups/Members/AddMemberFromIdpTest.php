<?php

namespace Tests\Feature\End2End\Groups\Members;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use App\Services\Idp\Fake\FakeIdpClient;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Models\Group as GroupModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Notifications\AddedToGroupNotification;

#[Group('groups')]
#[Group('members')]
class AddMemberFromIdpTest extends TestCase
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
        $this->user->person()->create(Person::factory()->make()->toArray());
        $this->group = GroupModel::factory()->create();
        $this->userMember = MemberAdd::run($this->group, $this->user->person);

        $this->url = 'api/groups/'.$this->group->uuid.'/members/from-idp';

        $this->store->put([
            'id' => 'user_fake_zed',
            'email' => 'zed@example.com',
            'emails' => ['z.zardoz@other.org'],
            'first_name' => 'Zed',
            'last_name' => 'Zardoz',
        ]);
    }

    private function actingAsCoordinator(): void
    {
        MemberGrantPermissions::run(
            $this->userMember,
            collect([config('permission.models.permission')::factory()->create(['name' => 'members-invite', 'scope' => 'group'])])
        );
        Sanctum::actingAs($this->user->fresh());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['idp_id' => 'user_fake_zed', 'email' => 'zed@example.com'], $overrides);
    }

    #[Test]
    public function guests_and_unprivileged_members_cannot_add_from_the_idp()
    {
        $this->postJson($this->url, $this->payload())->assertStatus(401);

        Sanctum::actingAs($this->user);
        $this->postJson($this->url, $this->payload())->assertStatus(403);
    }

    #[Test]
    public function validates_the_request()
    {
        $this->actingAsCoordinator();

        $this->postJson($this->url, [])->assertStatus(422)->assertJsonValidationErrors(['idp_id', 'email']);
        $this->postJson($this->url, $this->payload(['idp_id' => 'user_fake_nobody']))->assertStatus(422)->assertJsonValidationErrors(['idp_id']);
        $this->postJson($this->url, $this->payload(['email' => 'not-on-identity@example.com']))->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('users', ['idp_id' => 'user_fake_zed']);
    }

    #[Test]
    public function creates_a_person_a_linked_user_and_the_membership_with_roles()
    {
        $this->actingAsCoordinator();
        $role = config('permission.models.role')::factory(['scope' => 'group'])->create();

        $response = $this->postJson($this->url, $this->payload([
            'role_ids' => [$role->id],
            'is_contact' => true,
            'notes' => 'met at the meeting',
        ]));

        $response->assertStatus(201)->assertJsonFragment(['group_id' => $this->group->id]);

        $person = Person::where('email', 'zed@example.com')->firstOrFail();
        $this->assertSame('Zed', $person->first_name);
        $this->assertSame('Zardoz', $person->last_name);
        $this->assertNotNull($person->user_id);
        $this->assertDatabaseHas('users', ['id' => $person->user_id, 'email' => 'zed@example.com', 'idp_id' => 'user_fake_zed', 'idp_provider' => 'clerk']);
        $this->assertDatabaseHas('group_members', ['group_id' => $this->group->id, 'person_id' => $person->id, 'is_contact' => 1, 'notes' => 'met at the meeting']);
        $this->assertEquals($role->id, $response->original->roles[0]->id);
        $this->assertCount(1, $this->store->all(), 'no second identity is created at the IdP');
    }

    #[Test]
    public function can_pick_a_secondary_address_and_typed_names()
    {
        $this->actingAsCoordinator();

        $this->postJson($this->url, $this->payload(['email' => 'z.zardoz@other.org', 'first_name' => 'Zedediah', 'last_name' => 'Zardoz-Smith']))->assertStatus(201);

        $person = Person::where('email', 'z.zardoz@other.org')->firstOrFail();
        $this->assertSame('Zedediah', $person->first_name);
        $this->assertSame('Zardoz-Smith', $person->last_name);
        $this->assertDatabaseHas('users', ['id' => $person->user_id, 'email' => 'z.zardoz@other.org']);
    }

    #[Test]
    public function derives_names_from_the_address_when_neither_side_has_them()
    {
        $this->actingAsCoordinator();
        $this->store->put(['id' => 'user_fake_anon', 'email' => 'jane.roe@example.com']);

        $this->postJson($this->url, ['idp_id' => 'user_fake_anon', 'email' => 'jane.roe@example.com'])->assertStatus(201);

        $this->assertDatabaseHas('people', ['email' => 'jane.roe@example.com', 'first_name' => 'Jane', 'last_name' => 'Roe']);
    }

    #[Test]
    public function tells_the_person_their_existing_account_signs_them_in()
    {
        $this->actingAsCoordinator();
        Notification::fake();

        $this->postJson($this->url, $this->payload())->assertStatus(201);

        $person = Person::where('email', 'zed@example.com')->firstOrFail();
        Notification::assertSentTo($person, AddedToGroupNotification::class, fn ($notification) => $notification->idpAccountLinked === true);
    }

    #[Test]
    public function backfills_the_identity_external_id_with_the_person_uuid()
    {
        $this->actingAsCoordinator();

        $this->postJson($this->url, $this->payload())->assertStatus(201);

        $person = Person::where('email', 'zed@example.com')->firstOrFail();
        $this->assertSame($person->uuid, $this->store->find('user_fake_zed')['external_id']);
    }

    #[Test]
    public function reuses_an_unregistered_person_with_the_same_address()
    {
        $this->actingAsCoordinator();
        $existing = Person::factory()->create(['email' => 'ZED@example.com', 'user_id' => null]);
        $count = Person::count();

        $this->postJson($this->url, $this->payload())->assertStatus(201);

        $this->assertSame($count, Person::count());
        $this->assertNotNull($existing->fresh()->user_id);
        $this->assertDatabaseHas('group_members', ['group_id' => $this->group->id, 'person_id' => $existing->id]);
    }

    #[Test]
    public function links_the_given_person_and_redeems_their_pending_invite()
    {
        $this->actingAsCoordinator();
        $person = Person::factory()->create(['email' => 'old-address@example.com', 'user_id' => null]);
        $invite = Invite::factory()->create(['person_id' => $person->id, 'redeemed_at' => null]);
        Notification::fake();

        $this->postJson($this->url, $this->payload(['person_id' => $person->id]))->assertStatus(201);

        $this->assertDatabaseHas('users', ['id' => $person->fresh()->user_id, 'email' => 'zed@example.com', 'idp_id' => 'user_fake_zed']);
        $this->assertNotNull($invite->fresh()->redeemed_at);
        $this->assertDatabaseHas('activity_log', ['subject_type' => Person::class, 'subject_id' => $person->id, 'activity_type' => 'invite-redeemed']);
        Notification::assertSentTo($person, AddedToGroupNotification::class);
    }

    #[Test]
    public function rejects_an_identity_linked_to_another_user()
    {
        $this->actingAsCoordinator();
        User::factory()->linkedToIdp('user_fake_zed')->create(['email' => 'other@example.com']);

        $this->postJson($this->url, $this->payload())->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('people', ['email' => 'zed@example.com']);
    }

    #[Test]
    public function rejects_an_address_owned_by_another_user()
    {
        $this->actingAsCoordinator();
        User::factory()->create(['email' => 'zed@example.com']);

        $this->postJson($this->url, $this->payload())->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('people', ['email' => 'zed@example.com']);
    }

    #[Test]
    public function rejects_a_person_who_already_has_an_account()
    {
        $this->actingAsCoordinator();
        $person = $this->user->person;

        $this->postJson($this->url, $this->payload(['person_id' => $person->id]))->assertStatus(422)->assertJsonValidationErrors(['person_id']);
    }

    #[Test]
    public function fails_fast_with_503_when_the_idp_is_unreachable()
    {
        $this->actingAsCoordinator();
        $people = Person::count();
        $users = User::count();
        $this->client->failNext();

        $this->postJson($this->url, $this->payload())->assertStatus(503);

        $this->assertSame($people, Person::count());
        $this->assertSame($users, User::count());
    }

    #[Test]
    public function is_not_available_when_the_idp_driver_is_null()
    {
        config(['idp.driver' => 'null']);
        $this->actingAsCoordinator();

        $this->postJson($this->url, $this->payload())->assertStatus(404);
    }
}
