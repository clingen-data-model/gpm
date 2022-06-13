<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Country;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\ActionRequest;

class ReportSummaryMake
{
    use AsCommand, AsController;

    public $commandSignature = 'reports:basic-counts';

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    public function handle(): array
    {
        return $this->pullData();
    }

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if ($request->header('accept') == 'application/json') {
            return $data;
        }

        $data = $this->csvTransformer->handle($this->handle());
        return response($data, 200, ['Content-type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        $command->info($this->csvTransformer->handle($this->handle()));
    }

    private function pullData()
    {
        $vcepStepsSummary = $this->getVcepStepSummary();
        return [
            ['VCEP genes', $this->getVcepGenesCount()],
            ['GCEP genes', $this->getGcepGenesCount()],
            ['Total EP members', $this->getTotalEpMembersCount()],
            ['VCEP appliations in definition', $vcepStepsSummary[1]],
            ['VCEP appliations in draft specs', $vcepStepsSummary[2]],
            ['VCEP appliations in pilot specs', $vcepStepsSummary[3]],
            ['VCEP appliations in sustained curation', $vcepStepsSummary[4]],
            ['VCEPs approved', $this->getGcepApprovedCount()],
            ['GCEPs applying', $this->getGcepApplyingCount()],
            ['GCEPs approved', $this->getGcepApprovedCount()],
            ['Countries represented', $this->getCountriesRepresentedCount()],
            ['Institutions represented', $this->getInstitutionsRepresentedCount()],
            ['People in many EPs', $this->getPeopleInManyEpsCount()],
        ];
    }

    private function getVcepGenesCount(): int
    {
        return Gene::query()
                ->whereHas('expertPanel', function ($q) {
                    $q->typeVcep();
                })
                ->count();
    }

    private function getGcepGenesCount(): int
    {
        return Gene::query()
                ->whereHas('expertPanel', function ($q) {
                    $q->typeGcep();
                })
                ->count();
    }

    private function getTotalEpMembersCount(): int
    {
        $query = GroupMember::isActive()
            ->selectRaw('count(distinct(person_id))')
            ->whereHas('group', function ($q) {
                $q->typeExpertPanel();
            });

        return $query->count();
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