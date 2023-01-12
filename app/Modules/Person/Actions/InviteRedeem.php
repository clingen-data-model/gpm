<?php
namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Modules\Person\Models\Invite;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\User\Actions\UserCreate;
use App\Modules\Person\Events\InviteRedeemed;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\Person\Http\Requests\InviteRedemptionRequest;

class InviteRedeem
{
    use AsController;
    public function __construct(private UserCreate $createUser)
    {
    }

    public function handle(Invite $invite, $data)
    {
        $invite->markRedeemed(Carbon::now())->save();

        // TODO: Extract to create User for person
        $user = $this->createUser->handle(
            name: $invite->person->first_name.' '.$invite->person->last_name,
            email: $data['email'],
            password: $data['password']
        );

        $invite->person
            ->user()
            ->associate($user)
            ->save();
        // END TODO


        Event::dispatch(new InviteRedeemed($invite, $user));

        return $invite;
    }

    public function asController(ActionRequest $request, $code)
    {
        $invite = Invite::findByCodeOrFail($code);
        if ($invite->hasBeenRedeemed()) {
            // throw ValidationException::withMessages(['code' => 'It looks like this invite has already been redeemed. Please log in to access your account, update your profile and complete any COI disclosures.']);
        }

        return $this->handle($invite, $request->all());
    }

    public function rules(): array
    {
        return [
            'code' => 'required'|'exists:invites,code',
            'email' => 'required|email|unique:users',
            'password' => 'required|max:255|confirmed',
        ];
    }
}
