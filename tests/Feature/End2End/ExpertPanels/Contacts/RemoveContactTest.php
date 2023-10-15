<?php

namespace Tests\Feature\End2End\ExpertPanels\Contacts;

use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * @group applications
 * @group expert-panels
 * @group contacts
 * @group membership
 */
class RemoveContactTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->person = Person::factory()->create();

        (new ContactAdd())->handle($this->expertPanel->uuid, $this->person->uuid);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function removes_contact_from_application(): void
    {
        $this->json('DELETE', '/api/applications/'.$this->expertPanel->uuid.'/contacts/'.$this->person->uuid)
            ->assertStatus(200);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $this->expertPanel->group->id,
            'person_id' => $this->person->id,
        ]);
    }

    /**
     * @test
     */
    public function responds_with_404_if_application_or_person_not_found(): void
    {
        $this->json('DELETE', '/api/applications/'.$this->expertPanel->uuid.'/contacts/bob-is-your-uncle')
            ->assertStatus(404);

        $this->json('DELETE', '/api/applications/'.Uuid::uuid4().'/contacts/'.$this->person->uuid)
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function response_with_422_if_person_not_a_contact_of_application(): void
    {
        $person2 = Person::factory()->create();

        $this->json('DELETE', '/api/applications/'.$this->expertPanel->uuid.'/contacts/'.$person2->uuid)
            ->assertStatus(422)
            ->assertJsonFragment([
                'contact' => ['The specified person is not a contact of this application.'],
            ]);
    }
}
