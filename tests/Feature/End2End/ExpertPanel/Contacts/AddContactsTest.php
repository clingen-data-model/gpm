<?php

namespace Tests\Feature\End2End\ExpertPanels\Contacts;

use App\Modules\ExpertPanel\Jobs\AddContact;
use Tests\TestCase;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Ramsey\Uuid\Uuid;

class AddContactsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();    
        $this->application = ExpertPanel::factory()->create();    
        $this->user = User::factory()->create();
    }
    

    /**
     * @test
     */
    public function adds_a_person_as_a_contact_to_an_application()
    {
        $person = Person::factory()->create();

        Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', ['person_uuid' => $person->uuid]);
        $response->assertStatus(200);
        $response->assertJson([
            'email' => $person->email,
            'uuid' => $person->uuid
        ]);
        $this->assertDatabaseHas('application_person', [
            'application_id' => $this->application->id,
            'person_id' => $person->id
        ]);
    }

    /**
     * @test
     */
    public function validates_new_contact_data()
    {
        $data = [];
        Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $data);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'person_uuid' => ['The person uuid field is required.'],
            ]
        ]);


        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', ['person_uuid'=>'this-is-not-a-uuid']);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'person_uuid' => ['The person uuid must be a valid UUID.'],
            ]
        ]);

        $uuid = Uuid::uuid4()->toString();

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', ['person_uuid' => $uuid]);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'person_uuid' => ['The person must already exist in the database.'],
            ]
        ]);
    }
    
    /**
     * @test
     */
    public function can_retrieve_contacts_for_an_application()
    {
        $contacts = Person::factory(2)->create();

        $contacts->each(function ($contact) {
            $this->application->addContact($contact);
        //     $job = new AddContact(
        //         applicationUuid: $this->application->uuid, 
        //         uuid: Uuid::uuid4(),
        //         first_name: $contact->first_name,
        //         last_name: $contact->last_name,
        //         email: $contact->email,
        //         phone: $contact->phone,
        //     );
        //     Bus::dispatch($job);
        });

        Sanctum::actingAs($this->user);
        $response = $this->json('GET', '/api/applications/'.$this->application->uuid.'/contacts');
        $response->assertStatus(200);
        $response->assertJson($contacts->toArray());
    }
}
