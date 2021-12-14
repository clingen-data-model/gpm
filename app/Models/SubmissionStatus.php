<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    public $hidden = ['created_at', 'updated_at'];
}
