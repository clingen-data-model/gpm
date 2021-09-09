<?php

namespace App\Modules\Person\Models;

use App\Models\HasEmail;
use App\Models\HasUuid;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Notifications\Notifiable;

class Person extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTimestamps;
    use HasUuid;
    use Notifiable;
    use HasEmail;

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

    // Relations
    public function logEntries()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function latestLogEntry()
    {
        return $this->morphOne(Activity::class, 'subject')
                ->orderBy('created_at', 'desc');
    }

    // Queries
    public static function findByEmail($email)
    {
        return static::where('email', $email)->first();
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
    

    // Factory
    protected static function newFactory()
    {
        return new PersonFactory();
    }
}
