<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ContactAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group applications
 * @group expert-panels
 * @group contacts
 * @group membership
 */
class ContactAddTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->action = app()->make(ContactAdd::class);

        $this->person = Person::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->create();
    }
    

    /**
     * @test
     */
    public function ContactAdded_event_logged_when_dispatched()
    {
        $this->action->handle($this->expertPanel->uuid, $this->person->uuid);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->id,
            'activity_type' => 'contact-added',
            'properties->person->uuid' => $this->person->uuid
        ]);
    }

    /**
     * @test
     */
    public function Person_added_ast_contact_for_ExpertPanel()
    {
        $this->action->handle($this->expertPanel->uuid, $this->person->uuid);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->expertPanel->group_id,
            'person_id' => $this->person->id
        ]);
    }
}
