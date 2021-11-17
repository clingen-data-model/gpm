<?php
namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Events\AttestationSigned;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;

class NhgriAttestationStore
{
    use AsObject;
    use AsController;

    public function handle(Group $group, Carbon $attestationDate)
    {
        if (!$group->isEp) {
            throw ValidationException::withMessages(['group' => 'Only expert panels have NHGRI attestations.']);
        }

        if ($group->expertPanel->nhgri_attestation_date) {
            return $group;
        }

        $group->expertPanel->nhgri_attestation_date = $attestationDate;
        $group->expertPanel->save();
        $group->touch();
        
        $event = new AttestationSigned($group, 'NHGRI', $attestationDate);
        Event::dispatch($event);
        
        return $group;
    }
    
    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);

        if (Auth::user()->cannot('makeAttestation', $group)) {
            throw new AuthorizationException('You do not have permission to sign attestations for this group.');
        }

        $attestationDate = Carbon::now();

        $this->handle($group, $attestationDate);

        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'attestation' => 'required|accepted'
        ];
    }
}
