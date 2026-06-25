<?php

namespace App\Modules\Group\Models;

use App\Modules\User\Models\User;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Modules\Group\Models\Submission;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScopeOfWorkVersion extends Model
{
    use HasFactory;
    use SoftDeletes;

    // NNED TO REPLACE THIS EITHER ENUM OR CONFIG VALUES
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVISIONS_REQUESTED = 'revisions_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISCARDED = 'discarded';

    protected $fillable = [
        'uuid',
        'group_id',
        'expert_panel_id',
        'major_version',
        'minor_version',
        'status',
        'base_version_id',
        'submission_id',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'expert_panel_id' => 'integer',
        'major_version' => 'integer',
        'minor_version' => 'integer',
        'base_version_id' => 'integer',
        'submission_id' => 'integer',
        'created_by' => 'integer',
        'submitted_by' => 'integer',
        'submitted_at' => 'datetime',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'version_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScopeOfWorkVersion $version) {
            if (!$version->uuid) {
                $version->uuid = (string) Str::uuid();
            }
        });
    }

    public function getVersionLabelAttribute(): string
    {
        return "{$this->major_version}.{$this->minor_version}";
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function baseVersion()
    {
        return $this->belongsTo(self::class, 'base_version_id');
    }

    public function snapshots()
    {
        return $this->hasMany(ScopeOfWorkSnapshot::class);
    }

    public function latestSnapshot()
    {
        return $this->hasOne(ScopeOfWorkSnapshot::class)->latestOfMany();
    }

    public function changes()
    {
        return $this->hasMany(ScopeOfWorkChange::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForGroup($query, Group|int $group)
    {
        $groupId = $group instanceof Group ? $group->id : $group;

        return $query->where('group_id', $groupId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActiveDraft($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_REVISIONS_REQUESTED,
        ]);
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}