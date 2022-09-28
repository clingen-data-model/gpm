<?php

namespace App\ControlledVocabularies;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasSynonymInterface
{
    public function synonyms(): MorphMany;

    public function scopeMatchesSynonym(Builder $query, string $keyword, ?string $operator = null): Builder;

    public function addSynonyms(string|array $synonym): self;
}
