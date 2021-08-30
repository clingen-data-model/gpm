<?php
namespace App\Modules\Group\Models\Traits;

use App\Modules\Group\Models\Group;

/**
 *
 */
trait BelongsToGroup
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
