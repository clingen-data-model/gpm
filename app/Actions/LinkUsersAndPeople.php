<?php
namespace App\Actions;

use Ramsey\Uuid\Uuid;
use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Person\Actions\PersonCreate;

class LinkUsersAndPeople
{
    use AsAction;

    public $commandSignature = 'users:link-to-person';

    public function __construct(private PersonCreate $createPerson)
    {
    }

    public function handle(Model $model)
    {
        if (get_class($model) == Person::class && !$model->isLinkedToUser()) {
            $this->linkPersonToUser($model);
        }

        if (get_class($model) == User::class && !$model->isLinkedToPerson()) {
            $this->linkUserToPerson($model);
        }
    }

    public function asCommand(Command $command): void
    {
        $command->info('Linking users to people');
        User::all()
            ->each(function ($user) {
                $this->handle($user);
            });
    }
    

    private function linkPersonToUser(Person $person)
    {
        $user = User::where(
            'email',
            $person->email
        )->first();
        if ($user) {
            $person->update(['user_id' => $user->id]);
            return;
        }
    }

    private function LinkUserToPerson(User $user)
    {
        $person = Person::where('email', $user->email)->first();
        if ($person) {
            $person->update(['user_id' => $user->id]);
            return;
        }

        [$first, $last] = explode(' ', $user->name);
        $person = $this->createPerson->handle(
            uuid: Uuid::uuid4()->toString(),
            first_name: $first,
            last_name: $last,
            email: $user->email,
            user_id: $user->id
        );
    }
}
