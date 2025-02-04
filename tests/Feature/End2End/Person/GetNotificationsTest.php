<?php

namespace Tests\Feature\End2End\Person;

use App\Modules\Person\Models\Person;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Stubs\TestDatabaseNotification;

class GetNotificationsTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);
        $this->person = Person::factory()->create(['user_id' => $this->user->id]);

        Notification::send($this->person, new TestDatabaseNotification('bonkers'));
    }

    /**
     * @test
     */
    public function person_can_get_their_own_notifications()
    {
        $this->json('get', '/api/people/'.$this->person->uuid.'/notifications/unread')
            ->assertStatus(200)
            ->assertJson([
                [
                    'type' => 'Tests\\Stubs\\TestDatabaseNotification',
                    'notifiable_type' => Person::class,
                    'notifiable_id' => $this->person->id,
                    'data' => ['test_attribute' => 'bonkers'],
                    'read_at' => null,
                ]
            ]);
    }
}
