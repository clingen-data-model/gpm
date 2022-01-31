<?php
namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Invite;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\User\Actions\UserDelete;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Person\Actions\PersonUnlinkUser;

class InviteReset
{
    use AsController;

    public function __construct(private PersonUnlinkUser $unlinkUser, private UserDelete $deleteUser)
    {
        //code
    }
    


    public function handle(Invite $invite, $data)
    {
        $invite->update(['redeemed_at' => null]);

        $person = $invite->person;
        $user = $invite->person->user;

        $this->unlinkUser->handle($person);
        $this->deleteUser->handle($user);

        return $invite;
    }

    public function asController(ActionRequest $request, $code)
    {
        $invite = Invite::findByCodeOrFail($code);
        $invite = $this->handle($invite, $request->all());
        $invite->load('person', 'person.user');
        
        return $invite;
    }

    public function authorize(ActionRequest $request)
    {
        return $request->user()->hasPermissionTo('people-manage');
    }
}
