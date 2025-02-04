<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Comment;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Notifications\CommentActivityNotification;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class CommentActivityNotificationTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->person = Person::factory()->create();
        $this->group = Group::factory()->create();
        $this->comment = Comment::factory()->create(['subject_type' => get_class($this->group), 'subject_id' => $this->group->id]);
    }

    /**
     * @test
     */
    public function gets_single_notification_for_a_comment_if_no_deleted_notification()
    {
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'created'));
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'updated'));
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'updated'));
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'approved'));

        $this->assertEquals(1, CommentActivityNotification::getValidUnique($this->person->unreadNotifications)->count());
    }

    /**
     * @test
     */
    public function returns_no_notification_if_comment_is_deleted()
    {
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'created'));
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'updated'));
        Notification::send($this->person, new CommentActivityNotification($this->group, $this->comment, 'deleted'));

        $this->assertEquals(0, CommentActivityNotification::getValidUnique($this->person->unreadNotifications)->count());
    }


}
