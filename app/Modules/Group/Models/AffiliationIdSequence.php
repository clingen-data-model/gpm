<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliationIdSequence extends Model
{
    protected $fillable = [
        'name',
        'next_value',
    ];
}
