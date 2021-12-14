<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionType extends Model
{
    use HasFactory;

    public $fillable = ['name', 'description'];
    public $hidden = ['created_at', 'updated_at'];
}
