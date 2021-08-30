<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'date_approved',
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
    ];

    protected $dates = [
        'date_approved',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hgnc()
    {
        return $this->belongsTo(\App\Models\Hgnc::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel()
    {
        return $this->belongsTo(\App\Models\ExpertPanel::class);
    }
}
