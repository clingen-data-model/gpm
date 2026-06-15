<?php

namespace App\Modules\Person\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Attestation extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'person_id',
        'experience_types',
        'other_text',
        'attestation_version',
        'attested_by',
        'attested_at',
        'revoked_at',
    ];

    protected $casts = [
        'attested_at' => 'datetime',
        'revoked_at'  => 'datetime',
        'deleted_at'  => 'datetime',
        'experience_types' => 'array',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function attestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attested_by');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('revoked_at');
    }

    public function scopeCurrentVersion($q, string $version = null)
    {
        return $q->where('attestation_version', $version ?? config('cam.attestation.version', '1.0'));
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at) && is_null($this->deleted_at);
    }

    public function isCurrentVersion(string $version = null): bool
    {
        return $this->attestation_version === ($version ?? config('cam.attestation.version', '1.0'));
    }
}
