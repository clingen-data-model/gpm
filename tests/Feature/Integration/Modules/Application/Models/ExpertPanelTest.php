<?php

namespace Tests\Feature\Integration\Modules\Application\Models;

use Tests\TestCase;
use App\Models\Document;
use App\Models\NextAction;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ContactAdded;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Events\DocumentAdded;
use App\Modules\ExpertPanel\Events\ContactRemoved;
use App\Modules\ExpertPanel\Jobs\CreateNextAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Events\NextActionAdded;
use App\Modules\ExpertPanel\Events\NextActionCompleted;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\ExpertPanel\Events\ApplicationAttributesUpdated;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;

class ExpertPanelTest extends TestCase
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
    public function name_is_working_name_if_long_base_name_is_null()
    {
        $application = ExpertPanel::factory()->create(['long_base_name' => null]);

        $this->assertEquals($application->name, $application->working_name);
    }

    /**
     * @test
     */
    public function name_is_long_base_name_if_not_null()
    {
        $application = ExpertPanel::factory()->create(['long_base_name' => 'Beans']);

        $this->assertEquals($application->name, $application->long_base_name);
    }
        
    /**
     * @test
     */
    public function fires_ContactAdded_event_when_contact_added()
    {
        $person = Person::factory()->create();
        $application = ExpertPanel::factory()->create();

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
        $application = ExpertPanel::factory()->create();

        $application->addContact($person);

        $this->assertLoggedActivity(
            $application,
            'Added contact '.$person->name.' to application.',
            [ 'person' => $person->toArray()],
        );

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $application->id,
            'activity_type' => 'contact-added'
        ]);
    }

    /**
     * @test
     */
    public function fires_DocumentAdded_event_fired()
    {
        $application = ExpertPanel::factory()->create();
        $document = Document::factory()->make(['document_type_id'=>config('documents.types.scope.id')]);

        Event::fake();
        $application->addDocument($document);

        Event::assertDispatched(DocumentAdded::class, function ($event) use ($application, $document) {
            return $event->application->uuid == $application->uuid
                && $event->document->uuid == $document->uuid;
        });
    }

    /**
     * @test
     */
    public function DocumentAdded_activity_logged_when_dispatched()
    {
        $application = ExpertPanel::factory()->create();
        $document = Document::factory()->make(['document_type_id'=>config('documents.types.scope.id')]);

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
        $application = ExpertPanel::factory()->create();

        $application->addDocument(Document::factory()->make(['document_type_id' => 1]));

        $this->assertEquals($application->documents->first()->version, 1);

        $application->addDocument(Document::factory()->make(['document_type_id' => 1]));

        $this->assertEquals($application->fresh()->documents()->count(), 2);
    }


    /**
     * @test
     */
    public function dispatches_ContactRemovedEvent()
    {
        $application = ExpertPanel::factory()->create();
        $person = Person::factory()->create();
        $application->addContact($person);

        Event::fake();
        $application->removeContact($person);

        Event::assertDispatched(ContactRemoved::class);
    }
    

    /**
     * @test
     */
    public function logs_contact_removed()
    {
        $application = ExpertPanel::factory()->create();
        $person = Person::factory()->create();
        $application->addContact($person);

        $application->removeContact($person);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $application->id,
            'description' => 'Removed contact '.$person->name
        ]);
    }

    /**
     * @test
     */
    public function raises_ApplicationCompleted_event()
    {
        $application = ExpertPanel::factory()->gcep()->create([
            'current_step' => 1
        ]);
    
        Event::fake();
        $application->completeApplication(Carbon::parse('2020-01-01'));
    
        Event::assertDispatched(ApplicationCompleted::class);
    }

    /**
     * @test
     */
    public function logs_Application_completed()
    {
        $application = ExpertPanel::factory()->gcep()->create([
            'current_step' => 1
        ]);
    
        $application->completeApplication(Carbon::parse('2020-01-01'));
        $this->assertLoggedActivity($application, 'Application completed.');
    }

    /**
     * @test
     */
    public function raises_ExpertPanelAttributesUpdated_event()
    {
        $application = ExpertPanel::factory()->gcep()->create();
    
        Event::fake();
        $application->setExpertPanelAttributes([
            'working_name' => 'test'
        ]);
    
        Event::assertDispatched(ExpertPanelAttributesUpdated::class);
    }

    /**
     * @test
     */
    public function logs_ExpertPanelAttributesUpdated()
    {
        $application = ExpertPanel::factory()->gcep()->create(['long_base_name'=>'aabb']);
    
        $application->setExpertPanelAttributes([
            'working_name' => 'test',
            'long_base_name' => 'aabb',
        ]);
        $this->assertLoggedActivity($application, 'Attributes updated: working_name = test');
    }
    
    /**
     * @test
     */
    public function appends_clingen_url_based_on_affiliation_id()
    {
        $application = ExpertPanel::factory()->make();

        $this->assertNull($application->clingen_url);

        $application->affiliation_id = '4000123';

        $this->assertEquals('https://clinicalgenome.org/affiliation/4000123', $application->clingen_url);
    }

    /**
     * @test
     */
    public function can_get_first_scope_document()
    {
        $application = ExpertPanel::factory()->create();
        $document1 = Document::factory()
                        ->make([
                            'document_type_id' => config('documents.types.scope.id'),
                            'version' => 1
                        ]);
        $document2 = Document::factory()
                        ->make([
                        'document_type_id' => config('documents.types.scope.id'),
                        'version' => 2
                    ]);
        
        $application->documents()->save($document1);
        $application->documents()->save($document2);

        $this->assertEquals($application->documents->count(), 2);

        $this->assertEquals($application->firstScopeDocument->id, $document1->id);
    }
    
    /**
     * @test
     */
    public function can_get_first_final_document()
    {
        $application = ExpertPanel::factory()->create();
        $document1 = Document::factory()
                        ->make([
                            'document_type_id' => config('documents.types.final-app.id'),
                            'version' => 1
                        ]);
        $document2 = Document::factory()
                        ->make([
                        'document_type_id' => config('documents.types.final-app.id'),
                        'version' => 2
                    ]);
        
        $application->documents()->save($document1);
        $application->documents()->save($document2);

        $this->assertEquals($application->documents->count(), 2);

        $this->assertEquals($application->firstFinalDocument->id, $document1->id);
    }

    /**
     * @test
     */
    public function removes_epType_display_name_suffix_when_storing_long_and_short_base_name()
    {
        $application = ExpertPanel::factory()->make();

        $application->long_base_name = 'Garbage Gut VCEP';
        $application->short_base_name = 'GG VCEP';

        $this->assertEquals('Garbage Gut', $application->getAttributes()['long_base_name']);
    }
}
