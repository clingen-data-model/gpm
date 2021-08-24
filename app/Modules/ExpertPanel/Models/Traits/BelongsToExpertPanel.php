<?php
namespace App\Modules\ExpertPanel\Models\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 *
 */
trait BelongsToExpertPanel
{
    public function expertPanel(): Relation
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function ep(): Relation
    {
        return $this->expertPanel();
    }
}
