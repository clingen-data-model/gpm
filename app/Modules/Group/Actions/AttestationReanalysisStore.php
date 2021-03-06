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

class AttestationReanalysisStore
{
    use AsObject;
    use AsController;

    public function handle(
        Group $group,
        ?bool $reanalysis_conflicting,
        ?bool $reanalysis_review_lp,
        ?bool $reanalysis_review_lb,
        ?String $reanalysis_other,
    ) {
        if (!$group->isEp) {
            throw ValidationException::withMessages(['group' => 'Only expert panels have Reanalysis & Discrepancy Resolution attestations.']);
        }

        if (!is_null($group->expertPanel->reanalysis_attestation_date)) {
            return $group;
        }

        $group->expertPanel->reanalysis_conflicting = $reanalysis_conflicting;
        $group->expertPanel->reanalysis_review_lp = $reanalysis_review_lp;
        $group->expertPanel->reanalysis_review_lb = $reanalysis_review_lb;
        $group->expertPanel->reanalysis_other = $reanalysis_other;

        $attestationDate = Carbon::now();
        if ($this->attestationCompleted($group)) {
            $group->expertPanel->reanalysis_attestation_date = $attestationDate;
        }

        $group->expertPanel->save();
        $group->touch();
        
        if ($this->attestationCompleted($group)) {
            $event = new AttestationSigned($group, 'Reanalysis', $attestationDate);
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

        $attestationDate = Carbon::now();

        $this->handle(
            $group,
            ...$request->only('reanalysis_conflicting', 'reanalysis_review_lp', 'reanalysis_review_lb', 'reanalysis_other')
        );

        return new GroupResource($group);
    }

    public function rules(): array
    {
        return [
            'reanalysis_conflicting' => 'required_without:reanalysis_other|boolean',
            'reanalysis_review_lp' => 'required_without:reanalysis_other|boolean',
            'reanalysis_review_lb' => 'required_without:reanalysis_other|boolean',
            'reanalysis_other' => 'required_without_all:reanalysis_conflicting,reanalysis_review_lp,reanalysis_review_lb'
        ];
    }

    public function getValidationMessages()
    {
        return [
            'reanalysis_other.required_without_all' => 'This field is required when no other options are checked.',
            'required_without' => 'This is required unless you explain differences.'
        ];
    }

    private function attestationCompleted($group)
    {
        if (
            (
                $group->expertPanel->reanalysis_conflicting
                && $group->expertPanel->reanalysis_review_lp
                && $group->expertPanel->reanalysis_review_lb
            )
            || $group->expertPanel->reanalysis_other
        ) {
            return true;
        }

        return false;
    }
}
