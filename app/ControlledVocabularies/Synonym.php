<?php

namespace App\ControlledVocabularies;

use Database\Factories\SynonymFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Synonym extends Model
{
    use HasFactory;

    public $fillable = ['name', 'synonym_of_type', 'synonym_of_id'];
    public $casts = [
        'id' => 'integer',
        'synonym_of_id' => 'integer'
    ];

    // RELATIONS
    public function synonymOf(): MorphTo
    {
        return $this->morphTo();
    }

    // SCOPES
    public function scopeForType($query, $type)
    {
        return $query->where('synonym_of_type', $type);
    }


    // FACTORY
    protected static function newFactory()
    {
        return new SynonymFactory();
    }

}
