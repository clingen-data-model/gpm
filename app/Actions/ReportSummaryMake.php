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
        $v = $this->getVcepStepSummary();
        $m = $this->getMembershipCounts();

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
        yield 'Active Individuals (has active group membership)' => $m['active_members'];
        yield 'Individuals in 1+ WGs' => $m['wg_members'];
        yield 'Individuals in 1+ CDWGs' => $m['cdwg_members'];
        yield 'Individuals in 1+ EPs' => $m['ep_members'];
        yield 'Individuals in 1+ GCEPs' => $m['gcep_members'];
        yield 'Individuals in 1+ VCEps' => $m['vcep_members'];
        yield 'Countries represented' => $this->getCountriesRepresentedCount();
        yield 'Institutions represented' => $this->getInstitutionsRepresentedCount();
        yield 'People in 2+ EPs' => $this->getPeopleInManyEpsCount();
        yield 'Individuals with demographics info' => $this->getEverFilledDemographics();
        yield 'Individuals with current demographics info (within last year)' => $this->getFilledDemographicsInLastYear();

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
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as vcep_members', [$vcepType])
            ->selectRaw('COUNT(DISTINCT CASE WHEN g.group_type_id = ? THEN gm.person_id END) as gcep_members', [$gcepType])
            ->first();

        $vcep_members = $row->vcep_members ?? 0;
        $gcep_members = $row->gcep_members ?? 0;

        return [
            'active_members' => (int) ($row->active_members ?? 0),
            'wg_members'     => (int) ($row->wg_members ?? 0),
            'cdwg_members'   => (int) ($row->cdwg_members ?? 0),
            'vcep_members'   => $vcep_members,
            'gcep_members'   => $gcep_members,
            'ep_members'     => (int) ($gcep_members + $vcep_members ?? 0),
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

    private function getNumberOfPublications(): int
    {
        return Publication::where('status', '=', 'enriched')->count();
    }

}
