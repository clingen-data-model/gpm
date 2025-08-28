<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\GeneFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use App\DataTransferObjects\GtGeneDto;
use App\DataTransferObjects\GtDiseaseDto;
use App\Services\Api\GtApiService;

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

    public function gene(): ?GtGeneDto
    {
        try {
            return Cache::remember("hgnc_id_{$this->hgnc_id}", 300, function () {
                $data = app(GtApiService::class)->getGeneSymbolById($this->hgnc_id);
                return $data ? GtGeneDto::fromArray($data) : null;
            });
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function disease(): ?GtDiseaseDto
    {
        try {
            return Cache::remember("mondo_id_{$this->mondo_id}", 300, function () {
                $data = app(GtApiService::class)->getDiseasesByMondoIds($this->mondo_id);
                return $data ? GtDiseaseDto::fromArray($data) : null;
            });
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
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