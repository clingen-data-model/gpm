<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ReportScvcepApplicationMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:scvcep-application';

    public function csvHeaders(): ?array
    {
        return [
            'Short name','Long name','Affiliation ID','Coordinators','Chairs','Current step',
            'step 1 received','step 1 approved','step 2 approved','step 3 approved',
            'step 4 received','step 4 approved','gpm_id',
        ];
    }

    public function streamRows(callable $push): void
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            $this->baseQuery()
                ->orderBy('id')
                ->chunkById(500, function ($scvceps) use ($push) {
                    foreach ($scvceps as $scvcep) {
                        $push($this->formatRow($scvcep));
                    }
                    $scvceps->each->unsetRelations();
                    gc_collect_cycles();
                });
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    private function baseQuery()
    {
        return ExpertPanel::typeScvcep()
            ->select([
                'id','group_id',
                'short_base_name','long_base_name','affiliation_id','current_step',
                'step_1_received_date','step_1_approval_date',
                'step_2_approval_date','step_3_approval_date',
                'step_4_received_date','step_4_approval_date',
            ])
            ->with([
                'coordinators.person:id,first_name,last_name',
                'chairs.person:id,first_name,last_name',
            ]);
    }

    private function formatRow(ExpertPanel $scvcep): array
    {
        $coordinators = $scvcep->coordinators
            ->map(fn($crd) => $crd->person->name)
            ->filter()->join(', ');

        $chairs = $scvcep->chairs
            ->map(fn($chair) => $chair->person->name)
            ->filter()->join(', ');

        return [
            'Short name'        => $scvcep->short_base_name,
            'Long name'         => $scvcep->long_base_name,
            'Affiliation ID'    => $scvcep->affiliation_id,
            'Coordinators'      => $coordinators,
            'Chairs'            => $chairs,
            'Current step'      => $scvcep->current_step,
            'step 1 received'   => carbonToString($scvcep->step_1_received_date, 'Y-m-d'),
            'step 1 approved'   => carbonToString($scvcep->step_1_approval_date, 'Y-m-d'),
            'step 2 approved'   => carbonToString($scvcep->step_2_approval_date, 'Y-m-d'),
            'step 3 approved'   => carbonToString($scvcep->step_3_approval_date, 'Y-m-d'),
            'step 4 received'   => carbonToString($scvcep->step_4_received_date, 'Y-m-d'),
            'step 4 approved'   => carbonToString($scvcep->step_4_approval_date, 'Y-m-d'),
            'gpm_id'            => $scvcep->group_id,
        ];
    }
}
