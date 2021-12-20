<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Email;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UserDefinedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecordsOutgoingMailTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->person = Person::factory()->create();
    }

    /**
     * @test
     */
    public function stores_email_in_emails_table()
    {
        $inviteNotification = new UserDefinedMailNotification('test subject', 'body body body');
        Notification::send($this->person, $inviteNotification);

        $this->assertDatabaseHas('emails', [
            'subject' => 'test subject',
            'to' => json_encode([$this->person->email => null])
        ]);
    }

    /**
     * @test
     */
    public function associates_email_record_with_person()
    {
        $inviteNotification = new UserDefinedMailNotification('test subject', 'body body body');
        Notification::send($this->person, $inviteNotification);


        $mail = Email::orderBy('created_at', 'desc')->first();
        
        $this->assertDatabaseHas('email_person', [
            'person_id' => $this->person->id,
            'email_id' => $mail->id
        ]);

        $this->assertEquals($this->person->emails->count(), 1);
    }
}
