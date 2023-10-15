<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Actions\ContactRemove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group applications
 * @group expert-panels
 * @group contacts
 */
class ContactRemoveTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
    }

    /**
     * @test
     */
    public function logs_contact_removed(): void
    {
        $expertPanel = ExpertPanel::factory()->create();
        $person = Person::factory()->create();
        (new ContactAdd)->handle($expertPanel->uuid, $person->uuid);

        (new ContactRemove)->handle($expertPanel->uuid, $person->uuid);

        $this->assertLoggedActivity($expertPanel->group, 'Removed contact '.$person->name);
    }
}
