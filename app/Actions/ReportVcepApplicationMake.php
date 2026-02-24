<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ReportVcepApplicationMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:vcep-application';

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
        DB::connection()->disableQueryLog();

        $this->baseQuery()
            ->orderBy('id')
            ->chunkById(500, function ($vceps) use ($push) {
                foreach ($vceps as $vcep) {
                    $push($this->formatRow($vcep));
                }
                $vceps->each->unsetRelations();
                gc_collect_cycles();
            });
    }

    private function baseQuery()
    {
        return ExpertPanel::typeVcep()
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

    private function formatRow(ExpertPanel $vcep): array
    {
        $coordinators = $vcep->coordinators
            ->map(fn($crd) => $crd->person->name)
            ->filter()->join(', ');

        $chairs = $vcep->chairs
            ->map(fn($chair) => $chair->person->name)
            ->filter()->join(', ');

        return [
            'Short name'        => $vcep->short_base_name,
            'Long name'         => $vcep->long_base_name,
            'Affiliation ID'    => $vcep->affiliation_id,
            'Coordinators'      => $coordinators,
            'Chairs'            => $chairs,
            'Current step'      => $vcep->current_step,
            'step 1 received'   => carbonToString($vcep->step_1_received_date, 'Y-m-d'),
            'step 1 approved'   => carbonToString($vcep->step_1_approval_date, 'Y-m-d'),
            'step 2 approved'   => carbonToString($vcep->step_2_approval_date, 'Y-m-d'),
            'step 3 approved'   => carbonToString($vcep->step_3_approval_date, 'Y-m-d'),
            'step 4 received'   => carbonToString($vcep->step_4_received_date, 'Y-m-d'),
            'step 4 approved'   => carbonToString($vcep->step_4_approval_date, 'Y-m-d'),
            'gpm_id'            => $vcep->group_id,
        ];
    }
}
