<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\Contracts\BelongsToExpertPanel;
use App\Models\Traits\BelongsToExpertPanel as TraitsBelongsToExpertPanel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $expert_panel_id
 * @property int $gene_id
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EvidenceSummaries extends Model implements BelongsToExpertPanel
{
    use HasFactory, SoftDeletes, TraitsBelongsToExpertPanel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expert_panel_id',
        'gene_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'expert_panel_id' => 'integer',
        'gene_id' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gene()
    {
        return $this->belongsTo(\App\Models\Gene::class);
    }
}
