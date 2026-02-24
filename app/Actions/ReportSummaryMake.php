<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Country;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Institution;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ReportSummaryMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:summary';

    public function csvHeaders(): ?array
    {
        return ['Metric', 'Value'];
    }

    public function streamRows(callable $push): void
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            foreach ($this->metrics() as $metric => $value) {
                $push(['Metric' => $metric, 'Value' => $value]);
            }
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    private function metrics(): iterable
    {
        $v = $this->getVcepStepSummary();

        yield 'Groups' => Group::count();
        yield 'Working Groups' => Group::wg()->count();
        yield 'CDWGs' => Group::cdwg()->count();
        yield 'VCEPs' => Group::vcep()->count();
        yield 'GCEPS' => Group::gcep()->count();
        yield 'VCEP applications in definition' => $v[1] ?? 0;
        yield 'VCEP applications in draft specs' => $v[2] ?? 0;
        yield 'VCEP applications in pilot specs' => $v[3] ?? 0;
        yield 'VCEP applications in sustained curation' => $v[4] ?? 0;
        yield 'VCEPs approved' => $this->getVcepApprovedCount();
        yield 'GCEPs applying' => $this->getGcepApplyingCount();
        yield 'GCEPs approved' => $this->getGcepApprovedCount();
        yield 'VCEP genes' => $this->getVcepGenesCount();
        yield 'GCEP genes' => $this->getGcepGenesCount();
        yield 'All Individuals' => Person::count();
        yield 'Active Individuals (has active group membership)' => Person::hasActiveMembership()->count();
        yield 'Individuals in 1+ WGs' => $this->getWgMembersCount();
        yield 'Individuals in 1+ CDWGs' => $this->getCdwgMembersCount();
        yield 'Individuals in 1+ EPs' => $this->getTotalEpMembersCount();
        yield 'Individuals in 1+ GCEPs' => $this->getGcepMembersCount();
        yield 'Individuals in 1+ VCEps' => $this->getVcepMembersCount();
        yield 'Countries represented' => $this->getCountriesRepresentedCount();
        yield 'Institutions represented' => $this->getInstitutionsRepresentedCount();
        yield 'People in 2+ EPs' => $this->getPeopleInManyEpsCount();
        yield 'Individuals with demographics info' => $this->getEverFilledDemographics();
        yield 'Individuals with current demographics info (within last year)' => $this->getFilledDemographicsInLastYear();
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
        return ExpertPanel::typeVcep()->approved()->count();
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

    private function getEverFilledDemographics(): int
    {
        return Person::whereNotNull('demographics_completed_date')
            ->count();
    }

    private function getFilledDemographicsInLastYear(): int
    {
        return Person::whereDate('demographics_completed_date', '>=', Carbon::now()->subYear())
            ->count();
    }

}
