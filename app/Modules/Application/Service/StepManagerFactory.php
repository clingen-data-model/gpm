<?php

namespace App\Modules\Application\Service;

use App\Modules\Application\Models\Application;
use App\Modules\Application\Service\Steps\StepManagerInterface;
use App\Modules\Application\Service\Steps\VcepDraftStepManager;
use App\Modules\Application\Service\Steps\VcepPilotStepManager;
use App\Modules\Application\Service\Steps\VcepFinalizeStepManager;
use App\Modules\Application\Service\Steps\GcepDefinitionStepManager;
use App\Modules\Application\Service\Steps\VcepDefinitionStepManager;
use App\Modules\Application\Exceptions\UnexpectedCurrentStepException;

class StepManagerFactory
{
    public function __invoke(Application $application): StepManagerInterface
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
