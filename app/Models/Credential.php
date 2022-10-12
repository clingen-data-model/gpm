<?php

namespace App\Models;

use App\ControlledVocabularies\HasSynonymInterface;
use App\ControlledVocabularies\HasSynonymTrait;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Credential extends Model implements HasSynonymInterface
{
    use HasFactory;
    use HasSynonymTrait;

    public $fillable = [
        'name',
        'approved',
    ];

    public $casts = [
        'id' => 'integer',
        'approved' => 'boolean',
    ];

    public $hidden = ['pivot'];

    // RELATIONS
    /**
     * The people that belong to the Credential
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }
}
