<?php

namespace App\Domain\Person\Models;

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
        'phone'
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
    static public function findByEmail($email)
    {
        return static::where('email', $email)->first();
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
    

    // Factory
    static protected function newFactory()
    {
        return new PersonFactory();
    }
    
}
