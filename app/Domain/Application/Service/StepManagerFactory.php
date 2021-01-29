<?php

namespace App\Domain\Application\Service;

use App\Domain\Application\Models\Application;
use App\Domain\Application\Service\Steps\StepManagerInterface;
use App\Domain\Application\Service\Steps\VcepDraftStepManager;
use App\Domain\Application\Service\Steps\VcepPilotStepManager;
use App\Domain\Application\Service\Steps\VcepFinalizeStepManager;
use App\Domain\Application\Service\Steps\GcepDefinitionStepManager;
use App\Domain\Application\Service\Steps\VcepDefinitionStepManager;
use App\Domain\Application\Exceptions\UnexpectedCurrentStepException;

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
