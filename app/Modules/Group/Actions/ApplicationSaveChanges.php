<?php

namespace App\Modules\Group\Actions;

use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Http\Resources\GroupResource;

class ApplicationSaveChanges
{
    use AsController;

    public function __construct(
        private MembershipDescriptionUpdate $memberDescription,
        private ScopeDescriptionUpdate $scopeDescription,
        private CurationReviewProtocolUpdate $curationReviewProtocol,
        private AttestationGcepStore $gcepAttestation,
        private AttestationNhgriStore $nhgriAttestation,
        private AttestationReanalysisStore $reanalysisAttestation,
        // private ExpertPanelNameUpdate $expertPanelName,
        // private ParentUpdate $groupParent
    ) {
    }



    public function handle(Group $group, $data): Group
    {
        $data = collect($data);
        $group = $this->scopeDescription->handle($group, $data->get('scope_description'));
        $group = $this->curationReviewProtocol->handle($group, $data);
        if ($group->isGcep) {
            $group = $this->gcepAttestation->handle($group, $data->toArray());
        }
        $group = $this->nhgriAttestation->handle($group, Carbon::parse($data->get('nhgri_attestation_date')));
        if ($group->isVcep) {
            $group = $this->memberDescription->handle($group, $data->get('membership_description'));
            $group = $this->reanalysisAttestation
                        ->handle(
                            $group,
                            $data->get('reanalysis_conflicting'),
                            $data->get('reanalysis_review_lp'),
                            $data->get('reanalysis_review_lb'),
                            $data->get('reanalysis_other'),
                            $data->get('reanalysis_attestation_date')
                        );
        }
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $group = $this->handle($group, $request->all());

        return new GroupResource($group);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('updateApplicationAttribute', $request->group);
    }
}
