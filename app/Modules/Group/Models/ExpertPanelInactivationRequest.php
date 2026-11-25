<?php

namespace App\Modules\Group\Models;

use App\Models\Traits\HasDocuments;
use App\Models\Traits\HasUuid;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExpertPanelInactivationRequest extends Model implements \App\Models\Contracts\HasDocuments
{
    use HasUuid, HasDocuments;

    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const REVISION_REQUESTED = 'revision_requested';
    public const PENDING_OC_REVIEW = 'pending_oc_review';
    public const APPROVED = 'approved';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function ($request) {
            $request->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected $casts = [
        'answers' => 'array', 'version' => 'integer', 'schema_version' => 'integer',
        'submitted_at' => 'datetime', 'approved_at' => 'datetime',
        'revision_requested_at' => 'datetime', 'forwarded_at' => 'datetime',
    ];

    public function group() { return $this->belongsTo(Group::class); }
    public function receivingGroup() { return $this->belongsTo(Group::class, 'receiving_group_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by_id'); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by_id'); }

    public static function eligible(Group $group): bool
    {
        return in_array($group->group_type_id, [
            config('groups.types.gcep.id'), config('groups.types.vcep.id'), config('groups.types.scvcep.id'),
        ]);
    }
}
