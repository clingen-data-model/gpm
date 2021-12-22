<?php
namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Invite;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class InviteReset
{
    use AsController;
    public function handle(Invite $invite, $data)
    {
        $invite->update(['redeemed_at' => null]);

        // Event::dispatch(new InviteReset($invite, $user));

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
