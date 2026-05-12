<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\ApplicationComplete;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use PHPUnit\Framework\Attributes\Test;

class ApplicationCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->action = app()->make(ApplicationComplete::class);
    }
    

    #[Test]
    public function raises_ApplicationCompleted_event()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create([
            'current_step' => 1
        ]);
    
        Event::fake();
        $this->action->handle($expertPanel, Carbon::parse('2020-01-01'));
    
        Event::assertDispatched(ApplicationCompleted::class);
    }

    #[Test]
    public function logs_Application_completed()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create([
            'current_step' => 1
        ]);
    
        $this->action->handle($expertPanel, Carbon::parse('2020-01-01'));
        $this->assertLoggedActivity($expertPanel->group, 'Application completed.');
    }
}
