<?php

namespace Tests\Feature\End2End;

use App\Modules\Person\Models\Person;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Stubs\TestDatabaseNotification;
use Tests\TestCase;

class MarkNotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();

        $this->user = $this->setupUser();
        $this->person = Person::factory()->create(['user_id' => $this->user->id]);
        Notification::send($this->person, new TestDatabaseNotification('test test test'));
        $this->notification = $this->person->unreadNotifications()->first();

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function user_who_is_not_notifiable_cannot_mark_notification_read(): void
    {
        $otherUser = $this->setupUser();
        $otherPerson = Person::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($otherUser);

        $this->makeRequest()
            ->assertStatus(403);

        $this->assertDatabaseHas('notifications', [
            'id' => $this->notification->id,
            'read_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function user_who_i_not_notifiable_can_mark_notification_read(): void
    {
        Carbon::setTestNow();

        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $this->notification->id,
            'read_at' => Carbon::now(),
        ]);
    }

    private function makeRequest()
    {
        $url = '/api/notifications/'.$this->notification->id;

        return $this->json('put', $url);
    }
}
