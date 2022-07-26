<?php

namespace App\Modules\User\Actions;

use Exception;
use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use App\Modules\User\Events\UserCreated;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class UserCreate
{
    use AsAction;

    public $commandSignature = 'user:create {--name= : name of new user} {--email= : email for new user} {--actingas= : Email of user doing to creating.}';

    public $commandDescription = 'Creates a user given name and email';



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Creates a user entity with name, email, and password.
     * Password value is hashed before storage. If password is null, a random password is created and hashed.
     *
     * @param string $name User's name
     * @param string $email Email for the user account
     * @param string|null $password Password (or null).
     *
     * @return void
     */
    public function handle(string $name, string $email, ?string $password = null): User
    {
        $pass = $password ?? uniqid();
        $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($pass)]);
        Event::dispatch(new UserCreated(user: $user));

        return $user;
    }

    public function asCommand(Command $command)
    {
        try {
            $this->authenticateUser($command);
            $name = $this->getUserName($command);
            $email = $this->getEmail($command);

            if ($command->confirm('You are about to create a new user for '.$name.' <'.$email.'>.  Do you want to continue', true)) {
                $newUser = $this->handle(name: $name, email: $email);
                if ($newUser) {
                    $command->info('Account created for '.$name.' with email '.$email);
                }
            }

            return 0;
        } catch (\Exception $e) {
            $command->error($e->getMessage());
            return 1;
        }
    }

    private function authenticateUser(Command $command)
    {
        $email = $command->option('actingas');
        if (!$email) {
            $email = $command->ask('Your email address:');
        }
        $password = $command->secret('Your password:');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = User::findByEmail($email);
            $command->info('Authenticated as '.$user->name);
            return true;
        }

        throw new Exception('You could not be authenticated.');
    }

    private function getUserName(Command $command)
    {
        $name = $command->option('name');
        if (empty($name)) {
            $name = $command->ask('New user\'s name:');
        }

        $validator = Validator::make(
            compact('name'),
            ['name' => 'required|max:255|min:2'],
            [
                            'required' => 'name cannot be empty.'
                        ]
        );
        if ($validator->fails()) {
            $messages = implode('; ', $validator->getMessageBag()->all());
            throw new Exception($messages);
        }

        return $name;
    }

    private function getEmail(Command $command)
    {
        $email = $command->option('email');

        if (empty($email)) {
            $email = $command->ask('New user\'s email address:');
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
            throw new Exception($messages);
        }

        return $email;
    }
}
