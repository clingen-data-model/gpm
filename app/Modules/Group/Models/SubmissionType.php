<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\SubmissionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubmissionType extends Model
{
    use HasFactory;

    public $fillable = ['name', 'description'];
    public $hidden = ['created_at', 'updated_at'];


    protected static function newFactory()
    {
        return new SubmissionTypeFactory();
    }
}
