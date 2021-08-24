<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasNotes
{
    public function notes(): MorphMany;
}
