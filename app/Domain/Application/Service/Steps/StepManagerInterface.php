<?php

namespace App\Domain\Application\Service\Steps;

interface StepManagerInterface
{
    public function canApprove(): bool;
    public function getUnmetRequirements():array;
    public function meetsAllRequirements():bool;
    public function isCurrentStep():bool;
    public function isLastStep():bool;

    // public function getDocuments():Collection;
    // public function getLogEntries():Collection;
}