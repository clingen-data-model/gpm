<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\ContactRemove;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('applications')]
#[Group('expert-panels')]
#[Group('contacts')]
class ContactRemoveTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
    }
    

    #[Test]
    public function logs_contact_removed()
    {
        $expertPanel = ExpertPanel::factory()->create();
        $person = Person::factory()->create();
        (new ContactAdd)->handle($expertPanel->uuid, $person->uuid);

        (new ContactRemove)->handle($expertPanel->uuid, $person->uuid);

        $this->assertLoggedActivity($expertPanel->group, 'Removed contact '.$person->name);
    }
}
