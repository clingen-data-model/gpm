<?php

namespace Tests\Feature\Integration;

use App\Models\Email;
use App\Modules\Person\Models\Person;
use App\Notifications\UserDefinedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecordsOutgoingMailTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->person = Person::factory()->create();
    }

    /**
     * @test
     */
    public function stores_email_in_emails_table(): void
    {
        $inviteNotification = new UserDefinedMailNotification('test subject', 'body body body');
        Notification::send($this->person, $inviteNotification);

        $this->assertDatabaseHas('emails', [
            'subject' => 'test subject',
            'to' => json_encode([['name' => null, 'address' => $this->person->email]]),
        ]);
    }

    /**
     * @test
     */
    public function associates_email_record_with_person(): void
    {
        $inviteNotification = new UserDefinedMailNotification('test subject', 'body body body');
        Notification::send($this->person, $inviteNotification);

        $mail = Email::orderByDesc('created_at')->first();

        $this->assertDatabaseHas('email_person', [
            'person_id' => $this->person->id,
            'email_id' => $mail->id,
        ]);

        $this->assertEquals($this->person->emails->count(), 1);
    }
}
