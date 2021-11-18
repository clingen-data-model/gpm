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
use phpDocumentor\Reflection\Types\Boolean;

class AttestationGcepStore
{
    use AsObject;
    use AsController;

    public function handle(
        Group $group,
        Carbon $attestationDate,
        Carbon $gciTrainingDate,
    ) {
        if (!$group->isGcep) {
            throw ValidationException::withMessages(['group' => 'Only expert panels have Reanalysis & Discrepancy Resolution attestations.']);
        }

        $group->expertPanel->utilize_gt = true;
        $group->expertPanel->utilize_gci = true;
        $group->expertPanel->curations_publicly_available = true;
        $group->expertPanel->pub_policy_reviewed = true;
        $group->expertPanel->draft_manuscripts = true;
        $group->expertPanel->recuration_process_review = true;
        $group->expertPanel->biocurator_training = true;
        $group->expertPanel->biocurator_mailing_list = true;
        $group->expertPanel->gci_training_date = $gciTrainingDate;
        $group->expertPanel->gcep_attestation_date = $attestationDate;
        $group->expertPanel->save();
        $group->touch();
        
        $event = new AttestationSigned($group, 'GCEP', $attestationDate);
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
        $gciTrainingDate = Carbon::parse($request->gci_training_date);

        $this->handle(
            group: $group,
            attestationDate: $attestationDate,
            gciTrainingDate: $gciTrainingDate
        );

        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'utilize_gt' => 'required|accepted',
            'utilize_gci' => 'required|accepted',
            'curations_publicly_available' => 'required|accepted',
            'pub_policy_reviewed' => 'required|accepted',
            'draft_manuscripts' => 'required|accepted',
            'recuration_process_review' => 'required|accepted',
            'biocurator_training' => 'required|accepted',
            'gci_training_date' => 'required|date',
            'biocurator_mailing_list' => 'required|accepted',
        ];
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This is required.',
            'accepted' => 'This is required.'
        ];
    }
}
