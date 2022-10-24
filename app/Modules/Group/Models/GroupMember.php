<?php

namespace App\Modules\Group\Models;

use Carbon\Carbon;
use App\Models\Traits\HasRoles;
use App\Models\Contracts\HasNotes;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\Coi;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\GroupMemberFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Models\Traits\HasNotes as HasNotesTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Modules\Group\Models\Contracts\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\ExpertPanel\Models\Contracts\BelongsToExpertPanel;
use App\Modules\Group\Models\Traits\BelongsToGroup as BelongstToGroupTrait;
use App\Modules\ExpertPanel\Models\Traits\BelongsToExpertPanel as BelongsToExpertPanelTrait;

/**
 * @property int $id
 * @property int $group_id
 * @property int $person_id
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $coi_last_completed
 */
class GroupMember extends Model implements HasNotes, BelongsToGroup, BelongsToExpertPanel
{
    use HasFactory;
    use SoftDeletes;
    use HasRoles;
    use HasNotesTrait;
    use BelongstToGroupTrait;
    use BelongsToExpertPanelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'person_id',
        'start_date',
        'end_date',
        'is_contact',
        'notes',
        'training_level_1',
        'training_level_2'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'person_id' => 'integer',
        'is_contact' => 'boolean',
        'training_level_1' => 'boolean',
        'training_level_2' => 'boolean',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    public static function boot():void
    {
        parent::boot();

        /*
         * Copy activity_type from properties json column to
         * activity_type column for indexing, speed of retrieval,
         * and accessor
         */
        static::saving(function ($model) {
            if (!$model->start_date) {
                $model->start_date = Carbon::now();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get all of the expertPanel for the GroupMember
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function expertPanel(): Relation
    {
        return $this->hasOneThrough(ExpertPanel::class, Group::class);
    }

    /**
     * Get all of the cois for the GroupMember
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cois(): HasMany
    {
        return $this->hasMany(Coi::class);
    }

    /**
     * Get the latestCoi associated with the GroupMember
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestCoi(): HasOne
    {
        return $this->hasOne(Coi::class)->latestOfMany();
    }


    public function scopeIsContact($query)
    {
        return $query->where('is_contact', 1);
    }

    public function scopeContact($query)
    {
        return $query->where('is_contact', 1);
    }

    public function scopeIsActive($query)
    {
        return $query->whereNull('end_date');
    }

    public function scopeIsRetired($query)
    {
        return $query->whereNotNull('end_date');
    }

    public function scopeIsActivatedUser($query)
    {
        return $query->whereHas('person', function ($q) {
            $q->isActivatedUser();
        });
    }

    public function scopeHasPendingCoi($query)
    {
        return $query->isActive()
            ->whereDoesntHave('cois', function ($q) {
                $q->where('completed_at', '>', Carbon::today()->subDays(365));
            });
    }



    // ACCESSORS
    public function getCoiLastCompletedAttribute()
    {
        $latestCoi =  $this->cois
                        ->filter(function ($coi) {
                            return !is_null($coi->completed_at);
                        })
                        ->sortByDesc('completed_at')
                        ->first();
        return $latestCoi ? $latestCoi->completed_at : null;
    }

    public function getCoiNeededAttribute()
    {
        if ($this->cois->count() == 0) {
            return true;
        }

        if ($this->coiLastCompleted->lt(Carbon::today()->subYear())) {
            return true;
        }

        return false;
    }

    public function getHasCoiRequirementAttribute()
    {
        return true;
    }

     public function getExpertiseAttribute(): ?string
     {
         return $this->person->expertises->count() > 0
            ? $this->person->expertises->pluck('name')->join(', ')
            : $this->legacy_expertise ?? '';
     }

         public function getRolesAsStringAttribute()
         {
             return $this->roles->pluck('name')->join(', ');
         }



    protected static function newFactory()
    {
        return new GroupMemberFactory();
    }

    /**
     * Added to force spatie/laravel-permission to allow Roles/Permissions with web guard to GroupMember.
     * NOTE: I'm not entirely sure why Spatie thinks it's important to link roles to guards
     *      While it could be used to separate sets of roles and permissions like I'm doing with the scope
     *      attribute, it locks you into only being able to grant roles to authenticatable models.  I guess
     *      their solution to our use case would be to use the 'Team' concept, but that doesn't really fit
     *      our authorization use cases.
     */
    public function guardName()
    {
        return 'web';
    }
}
