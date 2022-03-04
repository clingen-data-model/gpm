<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use App\Models\Email;
use Laravel\Sanctum\Sanctum;
use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use Tests\Stubs\TestMailNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResendMailTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();

        $this->user = $this->setupUser(permissions: ['mail-log-view']);
        $this->user->notify(new TestMailNotification());
        $this->mail = Email::query()->first();

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function user_without_permissions_cannot_resend_email()
    {
        $this->user->revokePermissionTo('mail-log-view');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function user_with_permissions_can_resend_email()
    {
        Mail::fake();

        $this->makeRequest()
            ->assertStatus(200);

        Mail::assertSent(UserDefinedMailable::class);
        Mail::assertSent(UserDefinedMailable::class, function ($mail) {
            return $mail->to == $this->mail->to
                && $mail->subject == $this->mail->subject
                && $mail->body == $this->mail->body
                && $mail->cc == $this->mail->cc
                // && $mail->bcc == $this->mail->bcc
                && $mail->replyTo == $this->mail->reply_to
            ;
        });
    }

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'to' => $this->mail->to,
            'subject' => $this->mail->subject,
            'body' => $this->mail->body,
            'cc' => is_array($this->mail->cc) ? $this->mail->cc : [],
            'bcc' => is_array($this->mail->bcc) ? $this->mail->bcc : [],
            'reply_to' => $this->mail->reply_to
        ];
        // dd($data);
        return $this->json('POST', '/api/mail', $data);
    }
}
