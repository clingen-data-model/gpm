<?php

namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\User\Actions\UserDelete;
use App\Modules\Group\Actions\MemberRemove;
use Lorisleiva\Actions\Concerns\AsController;

class PersonDelete
{
    use AsController;

    public function __construct(private UserDelete $deleteUser, private MemberRemove $removeMember)
    {
    }
    

    public function handle(Person $person)
    {
        if ($person->invite) {
            $person->invite->delete();
        }
        
        $person->memberships->each(function ($membership) {
            $this->removeMember->handle($membership, Carbon::now());
        });
        
        $user = $person->user;
        if ($user) {
            $person->update(['user_id' => null]);
            $this->deleteUser->handle($user);
        }
        $person->delete();
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $this->handle($person);
        
        return response('Person was deleted', 200);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
}
