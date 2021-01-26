<?php

namespace Tests\Feature\End2End\Applications\Contacts;

use App\Domain\Application\Jobs\AddContact;
use Tests\TestCase;
use App\Domain\Application\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddContactsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();    
        $this->application = Application::factory()->create();    
    }
    

    /**
     * @test
     */
    public function adds_a_contact_to_an_application()
    {
        $contacts = $this->makeContactData();

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $contacts[0]);
        $response->assertStatus(201);
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
    public function validates_new_contact_data()
    {
        $data = [];
        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $data);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'first_name' => ['The first name field is required.'],
                'last_name' => ['The last name field is required.'],
                'email' => ['The email field is required.'],
                'phone' => ['The phone field is required.'],
            ]
        ]);


        $data = [
            'first_name' => 'Aliqua anim et excepteur amet exercitation. Consequat duis fugiat qui labore laborum culpa amet. Exercitation eiusmod id velit excepteur incididunt minim magna cupidatat. Excepteur ullamco culpa ut labore exercitation laborum veniam. Cupidatat ex laborum di',
            'last_name' => 'Aliqua anim et excepteur amet exercitation. Consequat duis fugiat qui labore laborum culpa amet. Exercitation eiusmod id velit excepteur incididunt minim magna cupidatat. Excepteur ullamco culpa ut labore exercitation laborum veniam. Cupidatat ex laborum di',
            'email' => 'bob\'s your uncle'
        ];

        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/contacts', $data);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
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
            AddContact::dispatchNow($this->application, $contact);
        });

        $response = $this->json('GET', '/api/applications/'.$this->application->uuid.'/contacts');
        $response->assertStatus(200);
        $response->assertJson($contacts->toArray());
    }
    
}
