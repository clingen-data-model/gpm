<?php

namespace Tests\Feature\End2End\Users;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ArtisanCreateUserTest extends TestCase
{
    use FastRefreshDatabase;

    protected $admin;
    protected $name;
    protected $email;

    public function setup():void
    {
        parent::setup();
        
        $this->admin = User::factory()->create();
        $this->name = 'Louise Belcher';
        $this->email = 'louise@bobsburgers.com';
    }

    /**
     * @test
     */
    public function it_exits_if_credentials_are_bad()
    {
        $this->artisan('user:create')
            ->expectsQuestion('Your email address:', 'linda@bobsburgers.com')
            ->expectsQuestion('Your password:', 'wineiswonderful')
            ->expectsOutput('You could not be authenticated.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_asks_for_auth_info_if_and_new_user_info_and_creates_a_user()
    {
        $this->artisan('user:create')
            ->expectsQuestion('Your email address:', $this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsOutput('Authenticated as '.$this->admin->name);
    }
    
    /**
     * @test
     */
    public function it_asks_for_user_info_if_not_provided()
    {
        $this->artisan('user:create')
            ->expectsQuestion('Your email address:', $this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsOutput('Authenticated as '.$this->admin->name)
            ->expectsQuestion('New user\'s name:', $this->name)
            ->expectsQuestion('New user\'s email address:', $this->email)
            ->expectsConfirmation('You are about to create a new user for '.$this->name.' <'.$this->email.'>.  Do you want to continue', 'yes')
            ->expectsOutput('Account created for '.$this->name.' with email '.$this->email)
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_takes_user_email_new_name_and_email_as_options()
    {
        $this->artisan('user:create --actingas='.$this->admin->email.' --name="'.$this->name.'" --email="'.$this->email.'"')
            ->doesntExpectOutput('Your email address:')
            ->expectsQuestion('Your password:', 'password')
            ->expectsOutput('Authenticated as '.$this->admin->name)
            ->doesntExpectOutput('New user\'s name:')
            ->doesntExpectOutput('New user\'s email address:')
            ->expectsConfirmation('You are about to create a new user for '.$this->name.' <'.$this->email.'>.  Do you want to continue', 'yes')
            ->expectsOutput('Account created for '.$this->name.' with email '.$this->email)
            ->assertExitCode(0);
    }
    

    /**
     * @test
     */
    public function validates_new_name_and_email()
    {
        $this->artisan('user:create --actingas='.$this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsQuestion('New user\'s name:', '')
            ->expectsOutput("name cannot be empty.")
            ->assertExitCode(1);

        $this->artisan('user:create --actingas='.$this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsQuestion('New user\'s name:', 'test')
            ->expectsQuestion('New user\'s email address:', null)
            ->expectsOutput("email cannot be empty.")
            ->assertExitCode(1);

        $this->artisan('user:create --actingas='.$this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsQuestion('New user\'s name:', 'test')
            ->expectsQuestion('New user\'s email address:', 'farts')
            ->expectsOutput("email is not valid.")
            ->assertExitCode(1);

        $this->artisan('user:create --actingas='.$this->admin->email)
            ->expectsQuestion('Your password:', 'password')
            ->expectsQuestion('New user\'s name:', 'test')
            ->expectsQuestion('New user\'s email address:', $this->admin->email)
            ->expectsOutput("email is already in use.")
            ->assertExitCode(1);
    }
}
