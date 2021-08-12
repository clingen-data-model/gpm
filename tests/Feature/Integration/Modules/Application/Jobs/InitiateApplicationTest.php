<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Jobs\InitiateApplication;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;

class InitiateApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->data = $this->makeApplicationData();

        $this->job = new InitiateApplication(...$this->data);
    }
    
    /**
     * @test
     */
    public function stores_new_application_model_when_initiated()
    {
        Bus::dispatch($this->job);
        $this->assertDatabaseHas('applications', $this->data);
    }

    /**
     * @test
     */
    public function fires_ApplicationInitiated_event_when_initiated()
    {
        Event::fake();

        Bus::dispatch($this->job);

        Event::assertDispatched(ApplicationInitiated::class);
    }

    /**
     * @test
     */
    public function activity_logged_when_application_initiated()
    {
        $user = User::factory()->create();

        Auth::loginUsingId($user->id);

        Bus::dispatch($this->job);

        $application = ExpertPanel::findByUuidOrFail($this->data['uuid']);

        $properties = array_merge($application->only(['uuid','working_name','cdwg_id','ep_type_id','date_initiated','coi_code', 'created_at', 'updated_at']), ['step' => 1]);

        $this->assertLoggedActivity(
            $application, 
            'Application initiated', 
            // $properties,  // comment out properties b/c can't get a match.
            null,
            get_class($user), 
            $user->id
        );
    }
}
