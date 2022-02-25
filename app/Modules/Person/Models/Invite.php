<?php

namespace App\Modules\Person\Models;

use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invite extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $fillable = [
        'code',
        'first_name',
        'last_name',
        'email',
        'inviter_id',
        'inviter_type',
        'person_id',
        'redeemed_at',
    ];

    public $casts = [
        'inviter_id' => 'integer',
        'person_id' => 'integer',
        'redeemed_at' => 'datetime'
    ];

    protected static function booted()
    {
        static::saving(function ($invite) {
            if (!$invite->code) {
                $invite->code = bin2hex(random_bytes(8));
            }
        });
    }
    

    /**
     * DOMAIN
     */
    public function hasBeenRedeemed(): bool
    {
        return !is_null($this->redeemed_at);
    }

    public function markRedeemed(): static
    {
        $this->redeemed_at = Carbon::now();
        return $this;
    }
    

    /**
     * RELATIONS
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
        
    public function inviter(): MorphTo
    {
        return $this->morphTo('inviter');
    }

    /**
     * SCOPES
     */

    public function scopeRedeemed($query)
    {
        return $query->whereNotNull('redeemed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('redeemed_at');
    }

    public function scopeForGroup($query, $group)
    {
        $groupId = $group;
        if (is_object($group)) {
            $groupId = $group->id;
        }
        return $query->where('group_id', $groupId);
    }
    
    /**
     * QUERIES
     */

    public static function findByCode($code): ?static
    {
        return static::where('code', $code)->first();
    }

    public static function findByCodeOrFail($code): static
    {
        return static::where('code', $code)->sole();
    }

    /**
     * ACCESSORS
     */

    public function getUrlAttribute()
    {
        return url('/invites/'.$this->code);
    }
    
    public function getIsPendingAttribute()
    {
        return is_null($this->redeemed_at);
    }
    
    public function getIsRedeemedAttribute()
    {
        return !is_null($this->redeemed_at);
    }
    

    public function gethasInviterAttribute()
    {
        return (bool)$this->group;
    }

    public function getCoiCodeAttribute()
    {
        return $this->inviter ? $this->inviter->coi_code : null;
    }
    


    public static function newFactory()
    {
        return new InviteFactory();
    }
}
