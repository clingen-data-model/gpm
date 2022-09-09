<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\Contracts\HasNotes;
use Illuminate\Database\Eloquent\Model;
use App\Modules\ExpertPanel\Models\Ruleset;
use Database\Factories\SpecificationFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasNotes as HasNotesTrait;
use App\Modules\Group\Models\Traits\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\ExpertPanel\Models\SpecificationStatus;
use App\Modules\ExpertPanel\Models\Contracts\BelongsToExpertPanel;
use App\Modules\Group\Models\Contracts\BelongsToGroup as ContractsBelongsToGroup;
use App\Modules\ExpertPanel\Models\Traits\BelongsToExpertPanel as TraitsBelongsToExpertPanel;

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
class Specification extends Model implements HasNotes, BelongsToExpertPanel, ContractsBelongsToGroup
{
    use HasFactory;
    use SoftDeletes;
    use HasNotesTrait;
    use BelongsToGroup;
    use TraitsBelongsToExpertPanel;

    protected $primaryKey = 'cspec_id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cspec_id',
        'expert_panel_id',
        'status',
        'name',
        'url',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'expert_panel_id' => 'integer',
        'status_id' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rulesets()
    {
        return $this->hasMany(Ruleset::class, 'specification_id');
    }

    // ACCESSORS
    public function getIdAttribute()
    {
        return $this->attributes['cspec_id'];
    }

    protected static function newFactory()
    {
        return new SpecificationFactory();
    }
}
