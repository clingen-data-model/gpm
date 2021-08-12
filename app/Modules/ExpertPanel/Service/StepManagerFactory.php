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
    public function __invoke(ExpertPanel  $application): StepManagerInterface
    {
        if ($application->ep_type_id == config('expert_panels.types.gcep.id') && $application->current_step == 1) {
            return new GcepDefinitionStepManager($application);
        }

        switch ($application->current_step) {
            case 1:
                return new VcepDefinitionStepManager($application);
            case 2:
                return new VcepDraftStepManager($application);
            case 3:
                return new VcepPilotStepManager($application);
            case 4:
                return new VcepFinalizeStepManager($application);
            default:
                break;
        }

        throw new UnexpectedCurrentStepException($application);
    }
    
}
