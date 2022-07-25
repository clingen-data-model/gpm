<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Country;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ReportSummaryMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:summary';

    public function handle(): array
    {
        $vcepStepsSummary = $this->getVcepStepSummary();
        return [
            ['Groups', Group::count()],
            ['Working Groups', Group::wg()->count()],
            ['CDWGs', Group::cdwg()->count()],
            ['VCEPs', Group::vcep()->count()],
            ['GCEPS', Group::gcep()->count()],
            ['VCEP applications in definition', $vcepStepsSummary[1]],
            ['VCEP applications in draft specs', $vcepStepsSummary[2]],
            ['VCEP applications in pilot specs', $vcepStepsSummary[3]],
            ['VCEP applications in sustained curation', $vcepStepsSummary[4]],
            ['VCEPs approved', $this->getGcepApprovedCount()],
            ['GCEPs applying', $this->getGcepApplyingCount()],
            ['GCEPs approved', $this->getGcepApprovedCount()],
            ['VCEP genes', $this->getVcepGenesCount()],
            ['GCEP genes', $this->getGcepGenesCount()],
            ['Individuals', Person::count()],
            ['Individuals in 1+ WGs', $this->getWgMembersCount()],
            ['Individuals in 1+ CDWGs', $this->getCdwgMembersCount()],
            ['Individuals in 1+ EPs', $this->getTotalEpMembersCount()],
            ['Individuals in 1+ GCEPs', $this->getGcepMembersCount()],
            ['Individuals in 1+ VCEps', $this->getVcepMembersCount()],
            ['Countries represented', $this->getCountriesRepresentedCount()],
            ['Institutions represented', $this->getInstitutionsRepresentedCount()],
            ['People in 2+ EPs', $this->getPeopleInManyEpsCount()],
        ];
    }

    private function getVcepGenesCount(): int
    {
        return Gene::query()
                ->distinct('hgnc_id')
                ->whereHas('expertPanel', function ($q) {
                    $q->typeVcep();
                })
                ->count();
    }

    private function getGcepGenesCount(): int
    {
        return Gene::query()
            ->distinct('hgnc_id')
            ->whereHas('expertPanel', function ($q) {
                        $q->typeGcep();
                    })
                    ->count();
    }

    private function getTotalEpMembersCount(): int
    {
        $query = GroupMember::isActive()
            ->distinct('person_id')
            ->whereHas('group', function ($q) {
                $q->typeExpertPanel();
            });

        return $query->count();
    }

    private function getWgMembersCount(): int
    {
        $query = GroupMember::isActive()
            ->distinct('person_id')
            ->whereHas('group', function ($q) {
                $q->wg();
            });

        return $query->count();
    }

    private function getCdwgMembersCount(): int
    {
        return GroupMember::isActive()
            ->distinct('person_id')
            ->whereHas('group', function ($q) {
                $q->cdwg();
            })->count();
    }

    private function getGcepMembersCount(): int
    {
        return GroupMember::isActive()
            ->distinct('person_id')
            ->whereHas('group', function ($q) {
                $q->Gcep();
            })->count();
    }
    
    private function getVcepMembersCount(): int
    {
        return GroupMember::isActive()
            ->distinct('person_id')
            ->whereHas('group', function ($q) {
                $q->Vcep();
            })->count();
    }
    

    private function getVcepStepSummary(): array
    {
        return DB::table('expert_panels')
                    ->select(['current_step'])
                    ->selectRaw('count(*) as count')
                    ->where('expert_panel_type_id', config('expert_panels.types.vcep.id'))
                    ->whereNull('date_completed')
                    ->whereNull('deleted_at')
                    ->groupBy('current_step')
                    ->get()
                    ->sortBy('current_step')
                    ->pluck('count', 'current_step')
                    ->toArray();
    }

    private function getVcepApprovedCount(): int
    {
        return ExpertPanel::typeVcep()->approved_count();
    }

    private function getGcepApplyingCount(): int
    {
        return ExpertPanel::typeGcep()->applying()->count();
    }

    private function getGcepApprovedCount(): int
    {
        return ExpertPanel::typeGcep()->approved()->count();
    }
    
    private function getCountriesRepresentedCount(): int
    {
        return Country::query()
                ->has('people')
                ->count();
    }

    private function getInstitutionsRepresentedCount(): int
    {
        return Institution::query()
                ->has('people')
                ->count();
    }
    
    private function getPeopleInManyEpsCount(): int
    {
        return Person::has('activeExpertPanels', '>', 1)
            ->count();
    }
    
    
    
    
}
