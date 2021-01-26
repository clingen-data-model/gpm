<?php

namespace Tests\Feature\Integration\Domain\Application\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Domain\Application\Models\Person;
use App\Domain\Application\Models\Application;
use App\Domain\Application\Events\ContactAdded;
use App\Domain\Application\Events\StepApproved;
use App\Domain\Application\Events\DocumentAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Application\Events\ApplicationInitiated;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        Carbon::setTestNow('2021-01-01');   
    }
    

    /**
     * @test
     */
    public function stores_new_application_model_when_initiated()
    {

        $data = $this->makeApplicationData();

        $application = Application::initiate(...$data);

        $this->assertDatabaseHas('applications', $data);
    }

    /**
     * @test
     */
    public function fires_ApplicationInitiated_event_when_initiated()
    {
        Event::fake();

        $data = $this->makeApplicationData();
        Application::initiate(...$data);

        Event::assertDispatched(ApplicationInitiated::class);
    }

    /**
     * @test
     */
    public function activity_log_entry_is_added_when_initiated()
    {
        $user = User::factory()->create();

        Auth::loginUsingId($user->id);

        $data = $this->makeApplicationData();
        $application = Application::initiate(...$data);

        $this->assertLoggedActivity(
            $application, 
            'Application initiated', 
            $application->getAttributes(), 
            get_class($user), $user->id
        );
    }
    
    /**
     * @test
     */
    public function fires_ContactAdded_event_when_contact_added()
    {
        $person = Person::factory()->create();
        $application = Application::factory()->create();

        Event::fake();
        $application->addContact($person);

        Event::assertDispatched(ContactAdded::class);
    }

    /**
     * @test
     */
    public function ContactAdded_event_logged_when_dispatched()
    {
        $person = Person::factory()->create();
        $application = Application::factory()->create();

        $application->addContact($person);

        $this->assertLoggedActivity(
            $application,
            'Added contact '.$person->name.' to application.',
            ['person'=>$person->toArray()]
        );
    }

    /**
     * @test
     */
    public function fires_StepApproved_event_when_step_approved_and_logs_activity()
    {
        $application = Application::factory()->create();

        $dateApproved = Carbon::now();
        $application->approveCurrentStep($dateApproved);

        $this->assertLoggedActivity($application, 'Step 1 approved', ['date_approved' => $dateApproved]);
    }

    /**
     * @test
     */
    public function fires_DocumentAdded_event_fired()
    {
        $application = Application::factory()->create();
        $document = Document::factory()->make(['document_category_id'=>config('documents.categories.scope.id')]);

        Event::fake();
        $application->addDocument($document);

        Event::assertDispatched(DocumentAdded::class, function($event) use ($application, $document) {
            return $event->application->uuid == $application->uuid
                && $event->document->uuid == $document->uuid;
        });
    }

    /**
     * @test
     */
    public function DocumentAdded_activity_logged_when_dispatched()
    {
        $application = Application::factory()->create();
        $document = Document::factory()->make(['document_category_id'=>config('documents.categories.scope.id')]);

        $application->addDocument($document);

        $this->assertLoggedActivity(
            $application, 
            description: 'Added version 1 of scope and membership application.'
        );
    }    

    /**
     * @test
     */
    public function sets_version_based_existing_versions()
    {
        $application = Application::factory()->create();

        $application->addDocument(Document::factory()->make(['document_category_id' => 1]));

        $this->assertEquals($application->documents->first()->version, 1);

        $application->addDocument(Document::factory()->make(['document_category_id' => 1]));

        $this->assertEquals($application->fresh()->documents()->count(), 2);

    }

    

    private function assertLoggedActivity(
        $application, 
        $description, 
        $properties = null, 
        $causer_type = null, 
        $causer_id = null
    )
    {

        $data = [
            'log_name' => 'applications',
            'description' => $description,
            'subject_type' => Application::class,
            'subject_id' => (string)$application->id,
            'causer_type' => $causer_type,
            'causer_id' => $causer_id
        ];

        if ($properties) {
            $data['properties'] = json_encode($properties);
        }
        $this->assertDatabaseHas('activity_log', $data);
    }
 
    
}
