<?php

namespace Tests\Feature\End2End\Applications\Contacts;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemoveContactTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->person = Person::factory()->create();
        
        $this->application->addContact($this->person);
    }

    /**
     * @test
     */
    public function removes_contact_from_application()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('DELETE', '/api/applications/'.$this->application->uuid.'/contacts/'.$this->person->uuid)
            ->assertStatus(200);

        $this->assertDatabaseMissing('application_person', [
            'application_id' => $this->application->id,
            'person' => $this->person->id
        ]);
    }

    /**
     * @test
     */
    public function responds_with_404_if_application_or_person_not_found()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('DELETE', '/api/applications/'.$this->application->uuid.'/contacts/bob-is-your-uncle')
            ->assertStatus(404);

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('DELETE', '/api/applications/'.Uuid::uuid4().'/contacts/'.$this->person->uuid)
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function response_with_422_if_person_not_a_contact_of_application()
    {
        $person2 = Person::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('DELETE', '/api/applications/'.$this->application->uuid.'/contacts/'.$person2->uuid)
            ->assertStatus(422)
            ->assertJsonFragment([
                'contact' => ['The specified person is not a contact of this application.']
            ]);
    }
    
    
    
    
}
