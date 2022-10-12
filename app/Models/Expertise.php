<?php

namespace App\Models;

use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use App\ControlledVocabularies\HasSynonymTrait;
use App\ControlledVocabularies\HasSynonymInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expertise extends Model implements HasSynonymInterface
{
    use HasFactory;
    use HasSynonymTrait;

    public $fillable = ['name', 'approved'];
    public $casts = [
        'id' => 'integer',
        'approved' => 'boolean'
    ];

    public $hidden = ['pivot'];

    /**
     * Get all of the people for the Expertise
     *
     * @return BelongsToMany
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }
}
