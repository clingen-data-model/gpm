<?php

namespace App\Domain\Application\Models;

use Illuminate\Database\Eloquent\Collection;

interface StepManagerInterface
{
    public function canApprove(): bool;
    public function getUnmetRequirements():array;
    public function meetsAllRequirements():bool;
    public function isCurrentStep():bool;

    // public function getDocuments():Collection;
    // public function getLogEntries():Collection;
}