<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'long_name',
        'is_versioned',
        'application_document'
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'long_name' => 'string',
        'is_versioned' => 'boolean',
        'application_document' => 'boolean'
    ];
}
