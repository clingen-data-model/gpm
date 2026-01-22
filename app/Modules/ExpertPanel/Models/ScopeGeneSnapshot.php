<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeGeneSnapshot extends Model
{
    protected $fillable = [
        'scope_gene_id',
        'curation_uuid',
        'check_key',
        'payload',
        'captured_at',
        'is_outdated',
        'last_compared_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'captured_at' => 'datetime',
        'last_compared_at' => 'datetime',
        'is_outdated' => 'boolean',
    ];

    public function scopeGene()
    {
        return $this->belongsTo(Gene::class, 'scope_gene_id');
    }

}
