<?php

namespace App\Modules\Person\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Person\Models\Person;

class CocAttestation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'person_id',
        'version',
        'completed_at',
        'expires_at',
        'data',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
