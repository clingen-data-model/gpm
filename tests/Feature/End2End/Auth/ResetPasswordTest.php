<?php

namespace Tests\Feature\End2End\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();    
    }

    /**
     * @test
     */
    public function validates_required_fields()
    {
        $response = $this->json('POST', '/api/reset-password', []);
        $response->assertStatus(422);

        $response->assertJson([
            // "message" => "The given data was invalid.",
            "errors" => [
                'token' => ['The token field is required.'],
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.'],
                // 'password_confirmation' => ['The password confirmation field is required.']
            ]
        ]);
    }

    /**
     * @test
     */
    public function validates_field_requirements()
    {
        $data = [
            'email' => 'bob',
            'token' => 'test',
            'password' => '123'
        ];

        $this->json('POST', '/api/reset-password', $data)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    'email' => ['The email must be a valid email address.'],
                    'password' => ['The password must be at least 8 characters.', 'The password confirmation does not match.'],
                ]
            ]);
    }

    /**
     * @test
     */
    public function resets_password_if_data_is_valid()
    {
        $user = User::factory()->create();
        $this->json('POST', 'api/send-reset-password-link', ['email'=>$user->email])
            ->assertStatus(200);
        $token = DB::table('password_resets')->select('token')->where('email', $user->email)->sole()->token;

        $response = $this->json('POST', 'api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'aNewPassword',
            'password_confirmation' => 'aNewPassword'
        ]);

        $response->assertStatus(200);
    }
    
    
    
    

}
