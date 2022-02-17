<?php

namespace App\Modules\Person\Models;

use App\Models\Email;
use App\Models\HasUuid;
use App\Models\HasEmail;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Race;
use App\Modules\Person\Models\Gender;
use Database\Factories\PersonFactory;
use App\Modules\Person\Models\Country;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Person\Models\Ethnicity;
use Illuminate\Notifications\Notifiable;
use App\Modules\Person\Models\Institution;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Person\Models\PrimaryOccupation;
use App\Modules\Group\Models\Traits\IsGroupMember;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTimestamps;
    use HasUuid;
    use Notifiable;
    use HasEmail;
    use IsGroupMember;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'user_id',
        'institution_id',
        'credentials',
        'biography',
        'profile_photo_path',
        'orcid_id',
        'hypothesis_id',
        'street1',
        'street2',
        'city',
        'state',
        'zip',
        'country_id',
        'timezone',
        'primary_occupation_id',
        'primary_occupation_other',
        'race_id',
        'race_other',
        'ethnicity_id',
        'gender_id',
        'gender_other',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'institution_id' => 'integer',
        'orcid_id' => 'integer',
        'hypothesis_id' => 'integer',
        'country_id' => 'integer',
        'primary_occupation_id' => 'integer',
        'race_id' => 'integer',
        'ethnicity_id' => 'integer',
        'gender_id' => 'integer',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * RELATIONS
     */

    public function activeGroups(): BelongsToMany
    {
        return $this->groups()->whereNull('pivot.end_date');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function logEntries()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function latestLogEntry()
    {
        return $this->morphOne(Activity::class, 'subject')
                ->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function primaryOccupation()
    {
        return $this->belongsTo(PrimaryOccupation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ethnicity()
    {
        return $this->belongsTo(Ethnicity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the user that owns the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The emails that belong to the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(Email::class, 'email_person', 'person_id', 'email_id');
    }


    /**
     * Get the invite associated with the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invite(): HasOne
    {
        return $this->hasOne(Invite::class);
    }

    /**
     * Get all of the membershipsWithPendingCoi for the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function membershipsWithPendingCoi(): HasMany
    {
        return $this->memberships()->isActive()->hasPendingCoi();
    }

    /**
     * SCOPES
     */

    public function scopeIsActivatedUser($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeHasPendingCois($query)
    {
        return $query->whereHas('memberships', function ($q) {
            $q->hasPendingCoi()->isActive();
        });
    }

    public function scopeHasPendingInvite($query)
    {
        return $query->whereHas('invite', function ($q) {
            $q->pending();
        });
    }
    

    /**
     * QUERIES
     */
    public static function findByEmail($email)
    {
        return static::where('email', $email)->first();
    }

    /**
     * ACCESSORS
     */
    public function getNameAttribute(): String
    {
        return $this->first_name.' '.$this->last_name;
    }
    

    /**
     * DOMAIN
     */
    public function isLinkedToUser(): bool
    {
        return !is_null($this->user_id);
    }
    


    // Factory
    protected static function newFactory()
    {
        return new PersonFactory();
    }
}
