<?php

namespace App\Notifications\Contracts;

use App\Modules\ExpertPanel\Models\ExpertPanel;

interface UserDefinedTemplate
{
    public function renderTemplate(ExpertPanel $expertPanel): string;

    public function renderSubject(ExpertPanel $expertPanel): string;

    public function getTo(): array;
    
    // public function getFrom(): string;
    
    public function getCC(): array;

    public function getAttachments(): array;
}