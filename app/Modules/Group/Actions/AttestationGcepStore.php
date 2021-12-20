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
        array $data
    ) {
        if (!$group->isGcep) {
            throw ValidationException::withMessages(['group' => 'Only GCEPs have GCEP Attestations.']);
        }

        $data = collect($data);

        $attestationFields = [
            'utilize_gt',
            'utilize_gci',
            'curations_publicly_available',
            'pub_policy_reviewed',
            'draft_manuscripts',
            'recuration_process_review',
            'biocurator_training',
            'biocurator_mailing_list',
            'gci_training_date',
        ];

        if (!is_null($group->expertPanel->gcep_attestation_date)) {
            return $group;
        }

        $allTrue = true;
        foreach ($attestationFields as $fieldName) {
            $group->expertPanel->{$fieldName} = $data->get($fieldName);

            if (!$data->get($fieldName)) {
                $allTrue = false;
            }
        }

        if ($allTrue) {
            $group->expertPanel->gcep_attestation_date = Carbon::now();
        }

        $group->expertPanel->save();
        $group->touch();
        
        if ($allTrue) {
            $event = new AttestationSigned($group, 'GCEP', Carbon::now());
            Event::dispatch($event);
        }
        
        return $group;
    }
    
    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);

        if (Auth::user()->cannot('makeAttestation', $group)) {
            throw new AuthorizationException('You do not have permission to sign attestations for this group.');
        }

        $data = $request->all();
        $data['gci_training_date'] = Carbon::parse($request->gci_training_date);

        $this->handle(
            group: $group,
            data: $data
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
