<?php
namespace App\Actions;

use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

class LinkUsersAndPeople
{
    use AsAction;

    public $commandSignature = 'users:link-to-person';

    public function handle(Model $model)
    {
        if (get_class($model) == Person::class && !$model->isLinkedToUser()) {
            $this->linkPersonToUser($model);
        }

        if (get_class($model) == User::class && !$model->isLinkedToPerson()) {
            $this->linkUserToPerson($model);
        }

    }

    private function linkPersonToUser(Person $person)
    {
        $user = User::where('email', $person->email)->first();
        if ($user) {
            $person->update(['user_id' => $user->id]);
            return;
        }
    }

    private function LinkUserToPerson(User $user)
    {
        $person = Person::where('email', $user->email)->first();
        if ($user) {
            $person->update(['user_id' => $user->id]);
            return;
        }
}
    
    
}