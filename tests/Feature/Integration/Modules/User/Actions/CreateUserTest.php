<?php

namespace Tests\Feature\Integration\Modules\User\Actions;

use Tests\TestCase;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use App\Modules\User\Jobs\CreateUser;
use App\Modules\User\Actions\UserCreate;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
    }
    

    #[Test]
    public function creates_user()
    {
        UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseHas('users', ['name' => 'Lana Kane', 'email' => 'lana@archer.com']);
    }

    #[Test]
    public function logs_user_created_event()
    {
        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseHas(
            'activity_log',
            [
                'log_name' => 'users',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'User created: Lana Kane <lana@archer.com> ('.$user->id.')'
            ]
        );
    }

    #[Test]
    public function mirrors_the_user_to_the_identity_provider_with_a_password_digest()
    {
        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertNotNull($user->idp_id);
        $client = app(FakeIdpClient::class);
        $idpUser = $client->getUser($user->idp_id);
        $this->assertSame('lana@archer.com', $idpUser->email);
        $this->assertSame('Lana', $idpUser->firstName);
        $this->assertSame('Kane', $idpUser->lastName);
        $this->assertNull($idpUser->externalId);
        // The digest came from users.password, so the random local password matches at the IdP.
        $this->assertSame($user->password, $idpUser->raw['password_digest']);
    }

    #[Test]
    public function associates_a_person_and_uses_its_uuid_as_external_id()
    {
        $person = Person::factory()->create(['first_name' => 'Lana', 'last_name' => 'Kane']);

        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com', person: $person);

        $this->assertSame($user->id, $person->fresh()->user_id);
        $this->assertSame($person->uuid, app(FakeIdpClient::class)->getUser($user->idp_id)->externalId);
    }

    #[Test]
    public function links_to_an_existing_identity_instead_of_creating_one()
    {
        $existing = app(FakeIdpClient::class)->createUser(['email' => 'lana@archer.com']);

        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertSame($existing->id, $user->idp_id);
        $this->assertCount(1, app(FakeIdpClient::class)->store()->all());
    }

    #[Test]
    public function does_not_mirror_when_mirroring_is_disabled()
    {
        config(['idp.mirror_new_users' => false]);

        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertNull($user->idp_id);
        $this->assertCount(0, app(FakeIdpClient::class)->store()->all());
    }
}
