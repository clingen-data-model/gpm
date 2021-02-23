<?php

namespace Tests\Feature\End2End\Applications\Contacts;

use App\Domain\Application\Jobs\AddContact;
use Tests\TestCase;
use App\Domain\Application\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Ramsey\Uuid\Uuid;

class AddContactsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();    
        $this->application = Application::factory()->create();    
        $this->user = User::factory()->create();
    }
    

    /**
     * @test
     */
    public function adds_a_contact_to_an_application()
    {
        $contacts = $this->makeContactData();

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $contacts[0]);
        $response->assertStatus(200);
        $response->assertJson($contacts[0]);
    }

    /**
     * @test
     */
    public function adds_existing_contact_if_email_matches_existing()
    {
        $contacts = $this->makeContactData();

        $person = Person::create($contacts[0]);

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $contacts[0]);
        $response->assertStatus(200);
        
        $this->assertEquals($response->original['email'], $contacts[0]['email']);
        $this->assertEquals($response->original['id'], $person->id);

        $this->assertEquals(Person::count(), 1);
    }

    /**
     * @test
     */
    public function does_not_try_to_add_the_same_contact_twice()
    {
        $contacts = $this->makeContactData();

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $contacts[0])
            ->assertStatus(200);
        
        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $contacts[0])
            ->assertStatus(200);

        $this->assertEquals(Person::count(), 1);
        
    }
    

    /**
     * @test
     */
    public function validates_new_contact_data()
    {
        $data = [];
        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $data);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'uuid' => ['The uuid field is required.'],
                'first_name' => ['The first name field is required.'],
                'last_name' => ['The last name field is required.'],
                'email' => ['The email field is required.'],
                'phone' => ['The phone field is required.'],
            ]
        ]);


        $data = [
            'uuid' => 'test-this-is-not-uuid',
            'first_name' => 'Aliqua anim et excepteur amet exercitation. Consequat duis fugiat qui labore laborum culpa amet. Exercitation eiusmod id velit excepteur incididunt minim magna cupidatat. Excepteur ullamco culpa ut labore exercitation laborum veniam. Cupidatat ex laborum di',
            'last_name' => 'Aliqua anim et excepteur amet exercitation. Consequat duis fugiat qui labore laborum culpa amet. Exercitation eiusmod id velit excepteur incididunt minim magna cupidatat. Excepteur ullamco culpa ut labore exercitation laborum veniam. Cupidatat ex laborum di',
            'email' => 'bob\'s your uncle'
        ];

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $data);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'uuid' => ['The uuid must be a valid UUID.'],
                'first_name' => ['The first name may not be greater than 256 characters.'],
                'last_name' => ['The last name may not be greater than 256 characters.'],
                'email' => ['The email must be a valid email address.'],
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

        $response = $this->json('GET', '/api/applications/'.$this->application->uuid.'/contacts');
        $response->assertStatus(200);
        $response->assertJson($contacts->toArray());
    }
    
}
