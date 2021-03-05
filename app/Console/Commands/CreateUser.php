<?php

namespace App\Console\Commands;

use App\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {--name= : name of new user} {--email= : email for new user} {--actingas= : Email of user doing to creating.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a user given name and email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->authenticateUser();
            $name = $this->getUserName();
            $email = $this->getEmail();

            if ($this->confirm('You are about to create a new user for '.$name.' <'.$email.'>.  Do you want to continue', true)) {
                $newUser = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make(uniqid())]);
                if ($newUser) {
                    $this->info('Account created for '.$name.' with email '.$email);
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

    }

    private function authenticateUser()
    {
        $email = $this->option('actingas');
        if (!$email) {
            $email = $this->ask('Your email address:');
        }
        $password = $this->secret('Your password:');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = User::findByEmail($email);
            $this->info('Authenticated as '.$user->name);
            return true;
        }

        throw new \Exception('You could not be authenticated.');
    }
    
    private function getUserName()
    {
        $name = $this->option('name');
        if (empty($name)) {
            $name = $this->ask('New user\'s name:');
        }

        $validator = Validator::make(
                        compact('name'), 
                        ['name' => 'required|max:255|min:2'], 
                        [
                            'required' => 'name cannot be empty.'
                        ]);
        if ($validator->fails()) {
            $messages = implode('; ', $validator->getMessageBag()->all());
            throw new \Exception($messages);
        }

        return $name;
    }
    
    private function getEmail()
    {
        $email = $this->option('email');

        if (empty($email)) {
            $email = $this->ask('New user\'s email address:');
        }

        $validator = Validator::make(
            ['email' => $email], 
            ['email' => 'required|email|unique:users,email'],
            [
                'email' => 'email is not valid.',
                'required' => 'email cannot be empty.',
                'unique' => 'email is already in use.',
            ]
        );

        if ($validator->fails()) {
            $messages = implode('; ', $validator->getMessageBag()->all());
            throw new \Exception($messages);
        }

        return $email;
    }
    
}
