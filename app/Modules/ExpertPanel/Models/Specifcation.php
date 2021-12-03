<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\Contracts\HasNotes;
use Illuminate\Database\Eloquent\Model;
use App\Modules\ExpertPanel\Models\Ruleset;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Contracts\BelongsToExpertPanel;
use App\Models\Traits\HasNotes as HasNotesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\ExpertPanel\Models\SpecificationStatus;
use App\Models\Traits\BelongsToExpertPanel as TraitsBelongsToExpertPanel;

/**
 * @property int $id
 * @property string $cspec_id
 * @property int $expert_panel_id
 * @property int $status_id
 * @property string $url
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Specifcation extends Model implements HasNotes, BelongsToExpertPanel
{
    use HasFactory, SoftDeletes, HasNotesTrait,TraitsBelongsToExpertPanel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cspec_id',
        'expert_panel_id',
        'status_id',
        'url',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'expert_panel_id' => 'integer',
        'status_id' => 'integer',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rulesets()
    {
        return $this->hasMany(Ruleset::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(SpecificationStatus::class);
    }
}
