<?php

namespace App\Modules\ExpertPanel\Service;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\Steps\StepManagerInterface;
use App\Modules\ExpertPanel\Service\Steps\VcepDraftStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepPilotStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepFinalizeStepManager;
use App\Modules\ExpertPanel\Service\Steps\GcepDefinitionStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepDefinitionStepManager;
use App\Modules\ExpertPanel\Exceptions\UnexpectedCurrentStepException;

class StepManagerFactory
{
    public function __invoke(ExpertPanel  $expertPanel): StepManagerInterface
    {
        if (
            $expertPanel->expert_panel_type_id == config('expert_panels.types.gcep.id')
            && $expertPanel->current_step == 1
        ) {
            return new GcepDefinitionStepManager($expertPanel);
        }

        switch ($expertPanel->current_step) {
            case 1:
                return new VcepDefinitionStepManager($expertPanel);
            case 2:
                return new VcepDraftStepManager($expertPanel);
            case 3:
                return new VcepPilotStepManager($expertPanel);
            case 4:
                return new VcepFinalizeStepManager($expertPanel);
            default:
                break;
        }

        throw new UnexpectedCurrentStepException($expertPanel);
    }
}
