<?php

namespace App\Modules\ExpertPanel\Actions;

use Dompdf\Dompdf;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\CoiData;
use Illuminate\Support\Facades\View;
use App\Modules\ExpertPanel\Models\Coi;
use Lorisleiva\Actions\Concerns\AsController;

class CoiReportMakePdf
{
    use AsController;

    public function __construct(private Dompdf $dompdf)
    {
    }
    
    public function handle(Group $group)
    {
        $members = $group->members()->with('person', 'latestCoi')->get();

        $cois = $members->map(function ($member) {
            $coi = $member->latestCoi;
            if (!$coi) {
                $coi = new Coi(['data' => new CoiData([])]);
            }
            return (object)[
                'name' => $member->person->name,
                'work_fee_lab' => $this->resolveValue($coi->data->work_fee_lab),
                'contributions_to_gd_in_ep' => $this->resolveValue($coi->data->contributions_to_gd_in_ep),
                'contributions_to_genes' => $this->resolveValue($coi->data->contributions_to_genes),
                'independent_efforts' => $this->resolveValue($coi->data->independent_efforts),
                'independent_efforts_details' => $this->resolveValue($coi->data->independent_efforts_details),
                'coi' => $this->resolveValue($coi->data->coi),
                'coi_details' => $this->resolveValue($coi->data->coi_details),
                'completed_at' => $coi->completed_at ? $coi->completed_at->format('Y-m-d') : null
             ];
        });
        $view = View::make('pdfs.group_coi_report', ['cois' => $cois, 'group' => $group]);

        $this->dompdf->loadHtml($view->render());
        $this->dompdf->setPaper('legal', 'landscape');
        $this->dompdf->render();
        $this->dompdf->stream();
    }

    private function resolveValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            switch ($value) {
                case 0:
                    return 'No';
                case 2:
                    return 'Unsure';
                default:
                    return 'Yes';
            }
        }
        return $value;
    }
}
