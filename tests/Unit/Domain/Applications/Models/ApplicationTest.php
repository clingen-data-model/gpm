<?php

namespace Tests\Unit\Domain\Applications\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Application\Events\ApplicationInitiated;

class ApplicationTest extends TestCase
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

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'applications',
            'description' => 'Application initiated',
            'subject_type' => Application::class,
            'subject_id' => $application->id,
            'properties' => json_encode($application->getAttributes()),
            'causer_type' => get_class($user),
            // 'caused_by' => $user->id
        ]);
    }
    
    
}
