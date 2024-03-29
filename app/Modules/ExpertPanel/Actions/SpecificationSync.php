<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use App\Modules\ExpertPanel\Models\SpecificationStatus;

class SpecificationSync
{
    public function handle(
        ExpertPanel $expertPanel,
        string $cspecId,
        string $name,
        string $status
    ): Specification {
        // $specification = $expertPanel->specifications()
        $specification = Specification::updateOrCreate(
            [
                'cspec_id' => $cspecId,
            ],
            [
                'name' => $name,
                'status' => $status,
                'expert_panel_id' => $expertPanel->id
            ]
        );

        return $specification;
    }
}
