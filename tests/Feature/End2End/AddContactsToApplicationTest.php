<?php

namespace Tests\Feature\End2End;

use App\Domain\Application\Jobs\AddContact;
use Tests\TestCase;
use App\Domain\Application\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddContactsToApplicationTest extends TestCase
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

        $response = $this->json('POST', '/api/application/'.$this->application->uuid.'/contacts', $contacts[0]);
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

        $response = $this->json('POST', '/api/application/'.$this->application->uuid.'/contacts', $contacts[0]);
        $response->assertStatus(200);
        
        $this->assertEquals($response->original['email'], $contacts[0]['email']);
        $this->assertEquals($response->original['id'], $person->id);

        $this->assertEquals(Person::count(), 1);
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

        $response = $this->json('GET', '/api/application/'.$this->application->uuid.'/contacts');
        $response->assertStatus(200);
        $response->assertJson($contacts->toArray());
    }
    
}
