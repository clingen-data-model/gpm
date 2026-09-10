<?php

namespace App\Models;

use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForeignComponentMember extends Model
{
    protected $fillable = [
        'person_uuid', 'organization_name', 'city', 'country_id', 'country_name',
        'location_source', 'added_at', 'last_unretired_at', 'removed_at',
        'active_membership_count', 'stream_membership_count', 'last_stream_message_id',
        'is_in_scope', 'stream_membership_state',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'added_at' => 'datetime',
        'last_unretired_at' => 'datetime',
        'removed_at' => 'datetime',
        'active_membership_count' => 'integer',
        'stream_membership_count' => 'integer',
        'last_stream_message_id' => 'integer',
        'is_in_scope' => 'boolean',
        'stream_membership_state' => 'array',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_uuid', 'uuid');
    }
}
