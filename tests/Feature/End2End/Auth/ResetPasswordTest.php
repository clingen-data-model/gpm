<?php

namespace Tests\Feature\End2End\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
    }

    #[Test]
    public function validates_required_fields()
    {
        $response = $this->json('POST', '/api/reset-password', []);
        $response->assertStatus(422);

        $response->assertJson([
            "errors" => [
                'token' => ['This is required.'],
                'email' => ['This is required.'],
                'password' => ['This is required.'],
            ]
        ]);
    }

    #[Test]
    public function validates_field_requirements()
    {
        $data = [
            'email' => 'bob',
            'token' => 'test',
            'password' => '123'
        ];

        $this->json('POST', '/api/reset-password', $data)
            ->assertStatus(422)
            ->assertJson([
                "errors" => [
                    'email' => ['The email must be a valid email address.'],
                ]
            ]);
    }

    #[Test]
    public function validates_password_rules_once_token_is_accepted()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->json('POST', '/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => '123',
        ])
            ->assertStatus(422)
            ->assertJson([
                "errors" => [
                    'password' => ['The password must be at least 8 characters.', 'The password confirmation does not match.'],
                ]
            ]);
    }

    #[Test]
    public function resets_password_if_data_is_valid()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->json('POST', 'api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'aNewPassword',
            'password_confirmation' => 'aNewPassword'
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('aNewPassword', $user->fresh()->password));
    }

    #[Test]
    public function rejects_an_invalid_token()
    {
        $user = User::factory()->create();

        $this->json('POST', 'api/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'aNewPassword',
            'password_confirmation' => 'aNewPassword'
        ])->assertStatus(422);
    }
}
