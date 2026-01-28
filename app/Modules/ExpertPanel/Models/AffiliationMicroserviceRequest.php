<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AffiliationMicroserviceRequest extends Model
{
    use HasFactory;

    protected $table = 'affiliation_microservice_requests';

    protected $fillable = [
        'request_uuid',
        'expert_panel_id',
        'payload',
        'http_status',
        'response',
        'status',
        'error',
    ];

    protected $casts = [
        'payload'  => 'array',
        'response' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';

    // Relationships
    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    // Derived (no DB column needed)
    public function getIsOpenAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markSuccess(int $httpStatus, array $response): void
    {
        $this->forceFill([
            'http_status' => $httpStatus,
            'response'    => $response,
            'status'      => self::STATUS_SUCCESS,
            'error'       => null,
        ])->save();
    }

    public function markFailed(int $httpStatus, string $error, ?array $response = null): void
    {
        $attrs = [
            'http_status' => $httpStatus,
            'status'      => self::STATUS_FAILED,
            'error'       => Str::limit($error, 250, ''),
        ];

        if ($response !== null) {
            $attrs['response'] = $response;   // only set when provided
        }

        $this->forceFill($attrs)->save();
    }

    // Helpful scopes
    public function scopeOpen($q) { return $q->where('status', self::STATUS_PENDING); }
    public function scopeForExpertPanel($q, $epId) { return $q->where('expert_panel_id', $epId); }
}
