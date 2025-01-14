<?php

namespace App\Modules\Group\Models;

use App\Models\Traits\HasUuid;
use App\Models\Contracts\HasNotes;
use App\Models\Traits\HasComments;
use App\Models\Contracts\HasMembers;
use Database\Factories\GroupFactory;
use App\Tasks\Contracts\TaskAssignee;
use App\Models\Contracts\HasDocuments;
use App\Models\Contracts\HasLogEntries;
use App\Models\Contracts\RecordsEvents;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\GroupType;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupStatus;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Models\Traits\HasNotes as HasNotesTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Group\Models\Contracts\HasSubmissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Tasks\Models\TaskAssignee as TaskAssigneeTrait;
use App\Models\Traits\HasDocuments as HasDocumentsTrait;
use App\Models\Traits\RecordsEvents as RecordsEventsTrait;
use App\Models\Traits\HasLogEntries as HasLogEntriesTraits;
use App\Models\Contracts\HasComments as ContractsHasComments;
use App\Modules\Group\Enums\CurationProduct;
use App\Modules\Group\Models\Traits\HasMembers as HasMembersTrait;
use App\Modules\Group\Models\Traits\HasSubmissions as HasSubmissionsTrait;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $group_type_id
 * @property int $group_status_id
 * @property int $parent_id
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Group extends Model implements HasNotes, HasMembers, RecordsEvents, HasDocuments, HasLogEntries, HasSubmissions, TaskAssignee, ContractsHasComments
{
    use HasFactory;
    use SoftDeletes;
    use HasNotesTrait;
    use HasMembersTrait;
    use RecordsEventsTrait;
    use HasUuid;
    use HasDocumentsTrait;
    use HasLogEntriesTraits;
    use HasSubmissionsTrait;
    use TaskAssigneeTrait;
    use HasComments;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'group_type_id',
        'group_status_id',
        'parent_id',
        'coi_code'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'group_type_id' => 'integer',
        'group_status_id' => 'integer',
        'parent_id' => 'integer',
    ];

    protected $with = [
        'type',
        'status',
    ];

    protected $appends = [
        'has_coi_requirement',
        'coi_url',
        'display_name'
    ];

    public static function booted()
    {
        static::deleted(function (Group $group) {
            $group->members->each->delete();
            if ($group->expertPanel) {
                $group->expertPanel->delete();
            }
        });
    }

    /**
     * RELATIONS
     */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function expertPanel(): HasOne
    {
        return $this->hasOne(ExpertPanel::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    // public function groupType(): BelongsTo
    // {
    // }

    public function type(): BelongsTo
    {
        return $this->belongsTo(GroupType::class, 'group_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupStatus(): BelongsTo
    {
        return $this->belongsTo(GroupStatus::class);
    }

    /**
     * Get the status that owns the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->groupStatus();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get all of the children for the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id', 'id');
    }

    public function cois()
    {
        return $this->hasMany(Coi::class, 'group_id');
    }

    /**
     * SCOPES
     */

    public function scopeOfType($query, $type)
    {
        $typeId = $type;
        if (is_object($type)) {
            $typeId = $type->id;
        }
        return $query->where('group_type_id', $typeId);
    }

    public function scopeCdwg($query)
    {
        return $query->ofType(config('groups.types.cdwg.id'));
    }

    public function scopeWorkingGroup($query)
    {
        return $query->ofType(config('groups.types.wg.id'));
    }

    public function scopeWg($query)
    {
        return $this->scopeWorkingGroup($query);
    }

    public function scopeTypeExpertPanel($query)
    {
        return $query->whereHas('type', function ($q) {
            $q->whereNotNull('curation_product');
        });
    }

    public function scopeVcep($query)
    {
        return $query->ofType(config('groups.types.vcep.id'));
    }

    public function scopeGcep($query)
    {
        return $query->ofType(config('groups.types.gcep.id'));
    }

    public function scopeCurationProduct($query, CurationProduct $product)
    {
        return $query->whereHas('type', function ($q) use ($product) {
            $q->where('curation_product', $product);
        });
    }

    public function getCoiUrlAttribute()
    {
        return url('/coi/'.$this->coi_code);
    }

    public function getIsExpertPanelAttribute(): bool
    {
        return in_array($this->group_type_id, [config('groups.types.gcep.id'),config('groups.types.vcep.id'), config('groups.types.scvcep.id')]);
    }

    public function getIsEpAttribute(): bool
    {
        return $this->getIsExpertPanelAttribute();
    }


    public function getIsVcepAttribute(): bool
    {
        return $this->group_type_id == config('groups.types.vcep.id');
    }

    public function getIsGcepAttribute(): bool
    {
        return $this->group_type_id == config('groups.types.gcep.id');
    }

    public function getIsScvcepAttribute(): bool
    {
        return $this->group_type_id == config('groups.types.scvcep.id');
    }

    public function getIsVcepOrScvcepAttribute(): bool
    {
        return $this->getCuratesVariantPathogenicity;
    }

    public function getCuratesVariantsAttribute(): bool
    {
        return $this->type->curation_product == CurationProduct::Variant;
    }

    public function getIsSomaticCancerAttribute(): bool
    {
        return $this->type->is_somatic_cancer;
    }


    public function getFullTypeAttribute()
    {
        return $this->type;
    }

    public function getDisplayNameAttribute()
    {
        $typeName = $this->type ? $this->type->name : '';
        return $this->name.' '.strtoupper($typeName);
    }

    public function getHasCoiRequirementAttribute()
    {
        return $this->group_status_id == config('groups.statuses.active.id') || $this->group_status_id == config('groups.statuses.applying.id');
    }

    // DOMAIN
    public static function findByCoiCode($code)
    {
        return static::where('coi_code', $code)->first();
    }

    public static function findByCoiCodeOrFail($code)
    {
        return static::where('coi_code', $code)->sole();
    }

    protected static function newFactory()
    {
        return new GroupFactory();
    }
}
