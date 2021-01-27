<?php

namespace App\Models;

use App\Domain\Application\Models\HasUuid;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NextAction extends Model
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;

    protected $fillable = [
        'entry',
        'date_created',
        'date_completed',
        'target_date',
        'step',
        'application_id',
        'uuid'
    ];

    protected $dates = [
        'date_created',
        'date_completed',
        'target_date'
    ];
}
