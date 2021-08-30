<?php

namespace App\Modules\Group\Models;

use App\Models\Traits\HasUuid;
use App\Models\Contracts\HasNotes;
use App\Models\Contracts\HasMembers;
use Database\Factories\GroupFactory;
use App\Models\Contracts\HasDocuments;
use App\Models\Contracts\RecordsEvents;
use App\Modules\Group\Models\GroupType;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupStatus;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Models\Traits\HasNotes as HasNotesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasDocuments as HasDocumentsTrait;
use App\Models\Traits\RecordsEvents as RecordsEventsTrait;
use App\Modules\Group\Models\Traits\HasMembers as HasMembersTrait;

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
class Group extends Model implements HasNotes, HasMembers, RecordsEvents, HasDocuments
{
    use HasFactory;
    use SoftDeletes;
    use HasNotesTrait;
    use HasMembersTrait;
    use RecordsEventsTrait;
    use HasUuid;
    use HasDocumentsTrait;

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


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function expertPanel()
    {
        return $this->hasOne(ExpertPanel::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupType()
    {
        return $this->belongsTo(GroupType::class);
    }

    public function type()
    {
        return $this->groupType();
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupStatus()
    {
        return $this->belongsTo(GroupStatus::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Group::class);
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

    public function scopeExpertPanel($query)
    {
        return $query->ofType(config('groups.types.expert_panel.id'));
    }

    public function scopeVcep($query)
    {
        return $query->expertPanel()
                ->whereHas('expertPanel', function ($q) {
                    $q->Vcep();
                });
    }

    public function scopeGcep($query)
    {
        return $query->expertPanel()
                ->whereHas('expertPanel', function ($q) {
                    $q->Gcep();
                });
    }

    protected static function newFactory()
    {
        return new GroupFactory();
    }
}
