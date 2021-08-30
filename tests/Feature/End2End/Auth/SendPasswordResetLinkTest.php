<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendPasswordResetLinkTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function returns_validation_errors_if_email_not_set()
    {
        $response = $this->json('POST', '/api/send-reset-password-link', []);
        $response->assertStatus(422);

        $response->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    'email' => ['The email field is required.']
                ]
            ]);
    }
    
    /**
     * @test
     */
    public function returns_validation_error_if_email_not_valid_email()
    {
        $this->json('POST', '/api/send-reset-password-link', ['email' => 'email'])
            ->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    'email' => ['The email must be a valid email address.']
                ]
            ]);
    }

    /**
     * @test
     */
    public function returns_error_if_email_not_registered()
    {
        $this->json('POST', '/api/send-reset-password-link', ['email' => 'email@example.com'])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'There was a problem with your submission.',
                'errors' => [
                    'email' => ['We can\'t find a user with that email address.']
                ]
            ]);
    }

    /**
     * @test
     */
    public function sends_reset_notification_if_email_valid_and_registered()
    {
        Notification::fake();
        $user = User::factory()->create();
        $response = $this->json('POST', '/api/send-reset-password-link', ['email' => $user->email]);
        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
