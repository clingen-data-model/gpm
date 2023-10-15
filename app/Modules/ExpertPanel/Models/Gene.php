<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\GeneTracker\Disease as GtDisease;
use App\Models\GeneTracker\Gene as GtGene;
use Database\Factories\GeneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $hgnc_id
 * @property int $expert_panel_id
 * @property string $gene_symbol
 * @property string $mondo_id
 * @property \Carbon\Carbon $date_approved
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Gene extends Model
{
    use HasFactory, SoftDeletes;

    public function getConnectionName()
    {
        return config('database.default');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hgnc_id',
        'expert_panel_id',
        'gene_symbol',
        'mondo_id',
        'disease_name',
        'date_approved',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date_approved' => 'datetime',
        'id' => 'integer',
        'hgnc_id' => 'integer',
        'expert_panel_id' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel(): BelongsTo
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    /**
     * Get the gene that owns the Gene
     */
    public function gene(): BelongsTo
    {
        return $this->belongsTo(GtGene::class, 'hgnc_id', 'hgnc_id');
    }

    /**
     * Get the disease that owns the Gene
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(GtDisease::class, 'mondo_id', 'mondo_id');
    }

    /**
     * SCOPES
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('date_approved');
    }

    public function scopeApplying($query)
    {
        return $query->whereNull('date_approved');
    }

    public static function newFactory()
    {
        return new GeneFactory();
    }
}
