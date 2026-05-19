<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScopeOfWorkChange extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const IMPACT_MAJOR = 'major';
    public const IMPACT_MINOR = 'minor';

    public const APPROVAL_YES = 'yes';
    public const APPROVAL_NO = 'no';
    public const APPROVAL_CONDITIONAL = 'conditional';

    protected $fillable = [
        'scope_of_work_version_id',
        'rule_key',
        'area',
        'change_type',
        'label',
        'entity_type',
        'entity_uuid',
        'entity_label',
        'field_name',
        'before_value',
        'after_value',
        'impact',
        'requires_approval',
        'approval_step',
        'approvers',
        'condition',
    ];

    protected $casts = [
        'id' => 'integer',
        'scope_of_work_version_id' => 'integer',
        'before_value' => 'array',
        'after_value' => 'array',
        'approval_step' => 'integer',
    ];

    public function version()
    {
        return $this->belongsTo(ScopeOfWorkVersion::class, 'scope_of_work_version_id');
    }

    public function requiresApproval(): bool
    {
        return in_array($this->requires_approval, [
            self::APPROVAL_YES,
            self::APPROVAL_CONDITIONAL,
        ], true);
    }

    public function isMajor(): bool
    {
        return $this->impact === self::IMPACT_MAJOR;
    }

    public function isMinor(): bool
    {
        return $this->impact === self::IMPACT_MINOR;
    }
}