<?php

namespace Tests\Feature\Person;

use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonIdentitiesEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Person $person;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupPermission('people-manage');
        $this->person = $this->personWithUser();
        $this->person->identities()->create(['issuer' => 'https://cilogon.org', 'sub' => 'sub-1', 'idp_name' => 'ORCID']);
    }

    private function personWithUser(): Person
    {
        $person = Person::factory()->create();
        $person->user()->associate(User::factory()->create(['email' => $person->email]))->save();

        return $person->fresh();
    }

    private function url(?int $identityId = null): string
    {
        return '/api/people/'.$this->person->uuid.'/identities'.($identityId ? '/'.$identityId : '');
    }

    #[Test]
    public function owner_can_list_and_remove_their_identities()
    {
        Sanctum::actingAs($this->person->user);
        $identity = $this->person->identities()->first();

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.idp_name', 'ORCID')
            ->assertJsonMissingPath('0.sub');

        $this->deleteJson($this->url($identity->id))->assertNoContent();
        $this->assertDatabaseMissing('person_identities', ['id' => $identity->id]);
    }

    #[Test]
    public function other_users_are_forbidden()
    {
        Sanctum::actingAs($this->personWithUser()->user);

        $this->getJson($this->url())->assertForbidden();
        $this->deleteJson($this->url($this->person->identities()->first()->id))->assertForbidden();
        $this->assertDatabaseCount('person_identities', 1);
    }

    #[Test]
    public function people_managers_can_remove_identities()
    {
        $admin = $this->personWithUser()->user;
        $admin->givePermissionTo('people-manage');
        Sanctum::actingAs($admin);

        $this->deleteJson($this->url($this->person->identities()->first()->id))->assertNoContent();
        $this->assertDatabaseCount('person_identities', 0);
    }

    #[Test]
    public function cannot_remove_another_persons_identity_through_your_own_url()
    {
        $other = $this->personWithUser();
        $theirs = $other->identities()->create(['issuer' => 'https://cilogon.org', 'sub' => 'sub-2']);
        Sanctum::actingAs($this->person->user);

        $this->deleteJson($this->url($theirs->id))->assertNotFound();
        $this->assertDatabaseHas('person_identities', ['id' => $theirs->id]);
    }
}
