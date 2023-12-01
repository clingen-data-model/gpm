<?php

namespace App\Modules\Group\Models\Contracts;

interface BelongsToGroup
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group();
}
