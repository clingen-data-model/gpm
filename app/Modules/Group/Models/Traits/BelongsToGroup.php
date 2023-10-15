<?php

namespace App\Modules\Group\Models\Traits;

use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToGroup
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
