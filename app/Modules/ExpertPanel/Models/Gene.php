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

    protected $appends = ['gt_gene'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function geneDto(): ?GtGeneDto
    {
        try {
            $hgncID = (int) $this->hgnc_id ?? 0;
            if ($hgncID <= 0) { return null; }

            return Cache::remember("hgnc_id_{$hgncID}", now()->addMinutes(5), function () use ($hgncID) {
                $data = app(GtApiService::class)->getGeneSymbolById($hgncID);
                return $data ? GtGeneDto::fromArray($data) : null;
            });
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function diseaseDto(): ?GtDiseaseDto
    {
        try {
            $mondoID = $this->mondo_id;
            if (empty($mondoID)) { return null; }

            return Cache::remember("mondo_id_{$mondoID}", now()->addMinutes(5), function () use ($mondoID) {
                $data = app(GtApiService::class)->getDiseasesByMondoIds([$mondoID]);
                return $data ? GtDiseaseDto::fromArray($data) : null;
            });
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    protected function gtGene(): Attribute
    {
        return Attribute::make(
            get: function () {
                $dto = $this->geneDto();
                return $dto ? [
                    'hgnc_id'     => $dto->hgnc_id,
                    'gene_symbol' => $dto->gene_symbol,
                    'omim_id'     => $dto->omim_id,
                    'hgnc_name'   => $dto->hgnc_name,
                    'hgnc_status' => $dto->hgnc_status,
                ] : null;
            }
        );
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
