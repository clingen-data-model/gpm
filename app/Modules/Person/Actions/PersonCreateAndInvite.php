<?php

namespace App\Modules\Person\Actions;

use Ramsey\Uuid\Uuid;
use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Modules\Person\Events\PersonCreated;
use Lorisleiva\Actions\Concerns\AsController;

class PersonCreateAndInvite extends PersonCreate
{
    use AsCommand;

    public $commandSignature = 'person:create {--first_name= : first name of new person} {--last_name= : last name of new person} {--email= : email for new person} {--actingas= : Email of person doing to creating.}';

    public $commandDescription = 'Creates a person given name and email';

    public function __construct(private PersonInvite $inviter)
    {
    }
    
    public function handle(
        string $uuid,
        string $first_name,
        string $last_name,
        string $email,
        ?string $phone = null,
        ?int $user_id = null,
    ): Person {
        $person = parent::handle($uuid, $first_name, $last_name, $email, $phone, $user_id);

        $this->inviter->handle($person);

        return $person;
    }

    public function asCommand(Command $command)
    {
        try {
            // $this->authenticateUser($command);
            list($first_name, $last_name) = $this->getUserName($command);
            $email = $this->getEmail($command);

            if ($command->confirm('You are about to create a new person for '.$first_name.' '.$last_name.' <'.$email.'>.  Do you want to continue', true)) {
                $newPerson = $this->handle(
                    uuid: Uuid::uuid4()->toString(),
                    first_name: $first_name,
                    last_name: $last_name,
                    email: $email
                );

                if ($newPerson) {
                    $command->info('Account created for '.$first_name.' '.$last_name.' with email '.$email);
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

        throw new \Exception('You could not be authenticated.');
    }

    private function getUserName(Command $command)
    {
        $first_name = $command->option('first_name');
        if (empty($first_name)) {
            $first_name = $command->ask('New user\'s first_name:');
        }

        $last_name = $command->option('last_name');
        if (empty($last_name)) {
            $last_name = $command->ask('New user\'s last_name:');
        }

        $validator = Validator::make(
            compact('first_name', 'last_name'),
            [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255'
            ],
            ['required' => 'name cannot be empty.']
        );
        if ($validator->fails()) {
            $messages = implode('; ', $validator->getMessageBag()->all());
            throw new \Exception($messages);
        }

        return [$first_name, $last_name];
    }

    private function getEmail(Command $command)
    {
        $email = $command->option('email');

        if (empty($email)) {
            $email = $command->ask('New user\'s email address:');
        }

        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|email|unique:people,email'],
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
