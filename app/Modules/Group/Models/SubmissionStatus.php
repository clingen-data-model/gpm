<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\SubmissionStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubmissionStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    public $hidden = ['created_at', 'updated_at'];

    protected static function newFactory()
    {
        return new SubmissionStatusFactory();
    }
}
