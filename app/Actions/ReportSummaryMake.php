<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Country;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Models\Publication;
use App\Modules\Person\Models\Institution;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\Cache;

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
        $cached = Cache::remember('report:basic-summary', now()->addMinutes(30), function () {
            return iterator_to_array($this->metricsUncached());
        });

        foreach ($cached as $metric => $value) {
            yield $metric => $value;
        }
    }

    private function metricsUncached(): iterable
    {
        $vcep = $this->getStepSummary(config('expert_panels.types.vcep.id'));
        $scvcep = $this->getStepSummary(config('expert_panels.types.scvcep.id'));
        $m = $this->getMembershipCounts();

        yield 'Groups' => Group::count();
        yield 'Working Groups' => Group::wg()->count();
        yield 'CDWGs' => Group::cdwg()->count();
        yield 'SC-CDWGs' => Group::sccdwg()->count();

        yield 'VCEPs' => Group::vcep()->count();
        yield 'SC-VCEPs' => Group::scvcep()->count();
        yield 'GCEPS' => Group::gcep()->count();

        yield 'VCEP applications in definition' => $vcep[1] ?? 0;
        yield 'VCEP applications in draft specs' => $vcep[2] ?? 0;
        yield 'VCEP applications in pilot specs' => $vcep[3] ?? 0;
        yield 'VCEP applications in sustained curation' => $vcep[4] ?? 0;
        yield 'VCEPs approved' => $this->getExpertPanelCount('typeVcep', 'approved');
        yield 'SC-VCEP applications in definition' => $scvcep[1] ?? 0;
        yield 'SC-VCEP applications in draft specs' => $scvcep[2] ?? 0;
        yield 'SC-VCEP applications in pilot specs' => $scvcep[3] ?? 0;
        yield 'SC-VCEP applications in sustained curation' => $scvcep[4] ?? 0;
        yield 'SC-VCEPs approved' => $this->getExpertPanelCount('typeScvcep', 'approved');
        yield 'GCEPs applying' => $this->getExpertPanelCount('typeGcep', 'applying');
        yield 'GCEPs approved' => $this->getExpertPanelCount('typeGcep', 'approved');
        yield 'VCEP genes' => $this->getGenesCountByExpertPanelType('typeVcep');
        yield 'SC-VCEP genes' => $this->getGenesCountByExpertPanelType('typeScvcep');
        yield 'GCEP genes' => $this->getGenesCountByExpertPanelType('typeGcep');
        yield 'All Individuals' => Person::count();
        yield 'Active Individuals (has active group membership)' => $m['active_members'];
        yield 'Individuals in 1+ WGs' => $m['wg_members'];
        yield 'Individuals in 1+ CDWGs' => $m['cdwg_members'];
        yield 'Individuals in 1+ SC-CDWGs' => $m['sccdwg_members'];
        yield 'Individuals in 1+ EPs' => $m['ep_members'];
        yield 'Individuals in 1+ GCEPs' => $m['gcep_members'];
        yield 'Individuals in 1+ VCEps' => $m['vcep_members'];
        yield 'Individuals in 1+ SC-VCEps' => $m['scvcep_members'];
        yield 'Countries represented' => $this->getCountriesRepresentedCount();
        yield 'Institutions represented' => $this->getInstitutionsRepresentedCount();
        yield 'People in 2+ EPs' => $this->getPeopleInManyEpsCount();
        yield 'Individuals with demographics info' => $this->getEverFilledDemographics();
        yield 'Individuals with current demographics info (within last year)' => $this->getFilledDemographicsInLastYear();
        yield 'Number of Publications' => $this->getNumberOfPublications();
        yield 'Individuals has taken Code of Conduct attestation and not expire per today\'s date' => $this->getPeopleWithCocCount();
    }

    private function getPeopleWithCocCount(): int
    {
        return Person::query()->whereHas('latestCocAttestation', function ($q) {
                $q->whereNotNull('completed_at')->whereNotNull('expires_at')->where('expires_at', '>', now());
            })->count();
    }

    private function getMembershipCounts(): array
    {
        $wgType   = config('groups.types.wg.id');
        $cdwgType = config('groups.types.cdwg.id');
        $sccdwgType = config('groups.types.sccdwg.id');
        $scvcepType = config('groups.types.scvcep.id');
        $vcepType = config('groups.types.vcep.id');
        $gcepType = config('groups.types.gcep.id');

        $row = DB::table('group_members as gm')            
            ->join('groups as g', 'g.id', '=', 'gm.group_id')
            ->whereNull('gm.deleted_at')
            ->whereNull('gm.end_date')
            ->whereNull('g.deleted_at')
            ->selectRaw('COUNT(DISTINCT gm.person_id) as active_members')
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as wg_members', [$wgType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as cdwg_members', [$cdwgType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as sccdwg_members', [$sccdwgType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as scvcep_members', [$scvcepType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as vcep_members', [$vcepType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as gcep_members', [$gcepType])
            ->first();

        $scvcep_members = (int) ($row->scvcep_members ?? 0);
        $vcep_members = (int) ($row->vcep_members )?? 0;
        $gcep_members = (int) ($row->gcep_members )?? 0;

        return [
            'active_members' => (int) ($row->active_members ?? 0),
            'wg_members'     => (int) ($row->wg_members ?? 0),
            'cdwg_members'   => (int) ($row->cdwg_members ?? 0),
            'sccdwg_members' => (int) ($row->sccdwg_members ?? 0),
            'scvcep_members' => $scvcep_members,
            'vcep_members'   => $vcep_members,
            'gcep_members'   => $gcep_members,
            'ep_members'     => (int) ($gcep_members + $vcep_members + $scvcep_members ?? 0),
        ];
    }

    private function getGenesCountByExpertPanelType(string $typeScope): int
    {
        return Gene::query()
            ->whereHas('expertPanel', function ($q) use ($typeScope) {
                $q->{$typeScope}();
            })
            ->distinct()
            ->count('hgnc_id');
    }

    private function getStepSummary($groupTypeID): array
    {
        return DB::table('expert_panels')
                    ->select(['current_step'])
                    ->selectRaw('count(*) as count')
                    ->where('expert_panel_type_id', $groupTypeID)
                    ->whereNull('date_completed')
                    ->whereNull('deleted_at')
                    ->groupBy('current_step')
                    ->get()
                    ->sortBy('current_step')
                    ->pluck('count', 'current_step')
                    ->toArray();
    }

    private function getExpertPanelCount(string $typeScope, string $statusScope): int
    {
        return ExpertPanel::query()
            ->{$typeScope}()
            ->{$statusScope}()
            ->count();
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

    private function getNumberOfPublications(): int
    {
        return Publication::where('status', '=', 'enriched')->count();
    }

}
