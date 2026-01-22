<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\GeneFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hgnc_id',
        'expert_panel_id',
        'gene_symbol',
        'tier',
        'mondo_id',
        'disease_name',
        'date_approved',
        'moi',
        'plan',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'hgnc_id' => 'integer',
        'expert_panel_id' => 'integer',
        'tier' => 'integer',
        'date_approved' => 'datetime',
        'plan' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
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

    protected function gtGene(): Attribute
    {
        return Attribute::make(
            get: function () {
                $plan = $this->plan ?? [];

                return [
                    'hgnc_id'     => (int) ($this->hgnc_id ?? data_get($plan, 'hgnc_id')),
                    'gene_symbol' => $this->gene_symbol ?? data_get($plan, 'gene_symbol'),
                    'omim_id'     => $this->omim_id ?? data_get($plan, 'omim_id'),
                    'hgnc_name'   => $this->hgnc_name ?? data_get($plan, 'hgnc_name'),
                    'hgnc_status' => $this->hgnc_status ?? data_get($plan, 'hgnc_status'),
                ];
            }
        );
    }
}
