<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\ContactRemove;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group applications
 * @group expert-panels
 * @group contacts
 */
class ContactRemoveTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
    }
    

    /**
     * @test
     */
    public function logs_contact_removed()
    {
        $expertPanel = ExpertPanel::factory()->create();
        $person = Person::factory()->create();
        (new ContactAdd)->handle($expertPanel->uuid, $person->uuid);

        (new ContactRemove)->handle($expertPanel->uuid, $person->uuid);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $expertPanel->id,
            'description' => 'Removed contact '.$person->name
        ]);
    }
}
