<?php

namespace App\Modules\Person\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonIdentity extends Model
{
    protected $fillable = [
        'issuer',
        'sub',
        'idp',
        'idp_name',
        'email',
        'orcid',
        'last_login_at',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
