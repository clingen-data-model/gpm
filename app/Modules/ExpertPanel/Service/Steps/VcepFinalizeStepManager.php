<?php

namespace App\Modules\ExpertPanel\Service\Steps;

use App\Modules\ExpertPanel\Models\ExpertPanel;

class VcepFinalizeStepManager extends AbstractStepManager
{
    public function __construct(private ExpertPanel  $expertPanel)
    {
    }

    public function isCurrentStep():bool
    {
        return $this->expertPanel->expert_panel_type_id == config('expert_panels.types.vcep.id')
            && $this->expertPanel->current_step == 4;
    }

    public function meetsAllRequirements():bool
    {
        return true;
    }

    public function getUnmetRequirements():array
    {
        return [];
    }

    public function isLastStep():bool
    {
        return true;
    }
}
