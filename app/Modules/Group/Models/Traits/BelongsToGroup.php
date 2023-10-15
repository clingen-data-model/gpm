<?php

namespace App\Modules\Group\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Group\Models\Group;

trait BelongsToGroup
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
