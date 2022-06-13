<?php

namespace App\Actions;
use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ReportVcepApplicationReport
{
    use AsController;
    use AsCommand;

    public $commandSignature = 'reports:vcep-application';

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    public function handle()
    {
        $data = $this->pullData();
        array_unshift($data, array_keys($data[0]));
        return $this->csvTransformer->handle($data);
    }

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        return response($data, 200, ['Content-type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        $command->info($this->handle());
    }
    

    public function authorize(ActionRequest $requeset):bool
    {
        return true;
    }

    private function pullData(): array
    {
        $vceps = ExpertPanel::typeVcep()
                    // ->applying()
                    ->with([
                        'coordinators',
                        'coordinators.person',
                        'chairs',
                        'chairs.person',
                        'group'
                    ])
                    ->get();
                    
        $data = $vceps->map(function ($vcep) {
            $coordinators = $vcep->coordinators->map(function ($crd) {
                return $crd->person->name;
            })->join(", ");
            $chairs = $vcep->chairs->map(function ($chair) {
                return $chair->person->name;
            })->join(", ");
            return [
                'Short name' => $vcep->short_base_name,
                'Long name' => $vcep->long_base_name,
                'Affiliation ID' => $vcep->affiliation_id,
                'Coordinators' => $coordinators,
                'Chairs' => $chairs,
                'Current step' => $vcep->current_step,
                'step 1 received' => carbonToString($vcep->step_1_received_date, 'Y-m-d'),
                'step 1 approved' => carbonToString($vcep->step_1_approval_date, 'Y-m-d'),
                'step 2 approved' => carbonToString($vcep->step_2_approval_date, 'Y-m-d'),
                'step 3 approved' => carbonToString($vcep->step_3_approval_date, 'Y-m-d'),
                'step 4 received' => carbonToString($vcep->step_4_received_date, 'Y-m-d'),
                'step 4 approved' => carbonToString($vcep->step_4_approval_date, 'Y-m-d'),
                'gpm_id' => $vcep->group_id,
            ];
        })->toArray();

        return $data;
    }
    

}