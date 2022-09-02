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
        SpecificationStatus $status
    ): Specification
    {
        $specification = $expertPanel->specifications()
            ->updateOrCreate(
                [
                    'cspec_id' => $cspecId,
                ],
                [
                    'name' => $name,
                    'status_id' => $status->id
                ]
            );

        return $specification;
    }
}
