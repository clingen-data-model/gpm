<?php

namespace App\Modules\ExpertPanel\Models\Contracts;

use App\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BelongsToExpertPanel
{
    public function expertPanel();
    public function ep();
}
