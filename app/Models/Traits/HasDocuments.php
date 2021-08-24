<?php

namespace App\Models\Traits;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Handles relations for Documents
 */
trait HasDocuments
{
    /**
     * Get all of the documents for the HasDocuments
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'owner');
    }
}
