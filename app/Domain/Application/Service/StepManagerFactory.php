<?php

namespace App\Domain\Application\Service;

use App\Domain\Application\Models\Application;
use App\Domain\Application\Models\StepManagerInterface;
use App\Domain\Application\Models\VcepDefinitionStepManager;
use App\Domain\Application\Exceptions\UnexpectedCurrentStepException;

class StepManagerFactory
{
    public function __invoke(Application $application): StepManagerInterface
    {
        // if ($application->ep_type_id == config('expert_panels.types.gcep.id') && $application->current_step == 1) {
        //     return new GcepDefinitionStep($application);
        // }

        switch ($application->current_step) {
            case 1:
                return new VcepDefinitionStepManager($application);
            // case 2:
            //     return new VcepDraftStep($application);
            // case 3:
            //     return new VcepPilotStep($application);
            // case 4:
            //     return new VcepFinalizeStep($application);
            default:
                break;
        }

        throw new UnexpectedCurrentStepException($application);
    }
    
}
