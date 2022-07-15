<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertPanelType extends Model
{
    use HasFactory;
    
    protected $fillable = ['name'];
    protected $hidden = ['created_at', 'updated_at'];
}
