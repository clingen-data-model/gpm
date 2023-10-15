<?php

namespace App\Modules\ExpertPanel\Models;

use App\Modules\ExpertPanel\Models\Contracts\BelongsToExpertPanel;
use App\Modules\ExpertPanel\Models\Traits\BelongsToExpertPanel as TraitsBelongsToExpertPanel;
use Database\Factories\EvidenceSummaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $expert_panel_id
 * @property int $gene_id
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EvidenceSummary extends Model implements BelongsToExpertPanel
{
    use HasFactory;
    use SoftDeletes;
    use TraitsBelongsToExpertPanel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expert_panel_id',
        'summary',
        'gene_id',
        'variant',
        'vci_url',
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

    public function gene(): BelongsTo
    {
        return $this->belongsTo(Gene::class);
    }

    // Factory support
    protected static function newFactory()
    {
        return new EvidenceSummaryFactory();
    }
}
