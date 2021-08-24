<?php

namespace App\Modules\Group\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BelongsToGroup
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group();
}
