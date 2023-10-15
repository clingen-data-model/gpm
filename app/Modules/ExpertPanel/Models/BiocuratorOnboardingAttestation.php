<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $expert_panel_id
 * @property string $data
 * @property \Carbon\Carbon $attested_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BiocuratorOnboardingAttestation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expert_panel_id',
        'data',
        'attested_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'attested_at' => 'datetime',
        'id' => 'integer',
        'expert_panel_id' => 'integer',
        'data' => 'array',
    ];

    public function expertPanel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ExpertPanel::class);
    }
}
