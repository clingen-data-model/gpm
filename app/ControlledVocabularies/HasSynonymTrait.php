<?php

namespace App\ControlledVocabularies;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Implementation of HasSynonymInterface
 */
trait HasSynonymTrait
{

    function getSynonymAttribute(): string
    {
        return 'name';
    }

    public function synonyms(): MorphMany
    {
        return $this->morphMany(Synonym::class, 'synonym_of');
    }

    public function scopeMatchesSynonym(Builder $query, string $keyword, ?string $operator = 'and'): Builder
    {
        $query->whereHas('synonyms', function ($q) use ($keyword, $operator) {
            $searchString = preg_replace('/\./', '', strtolower($keyword));
            return $q->where('synonyms.name', 'LIKE', '%'.$searchString.'%');
        });

        return $query;
    }

    public function addSynonyms(string|array $synonym): self
    {
        $synonyms = $synonym;
        if (is_string($synonym)) {
            $synonyms = [$synonym];
        }

        foreach ($synonyms as $syn) {
            $this->synonyms()->updateOrCreate(['name' => $syn, 'synonym_of_id' => $this->id]);
        }

        return $this;
    }
}
