<?php

namespace App\Modules\Group\Models;

use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\SubmissionFactory;
use App\Modules\Group\Models\SubmissionType;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Group\Models\SubmissionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Submission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'submission_type_id',
        'submission_status_id',
        'data',
        'approved_at',
        'notes',
        'submitter_id',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'submission_type_id' => 'integer',
        'submission_status_id' => 'integer',
        'data' => 'array',
        'approved_at' => 'datetime',
        'submitter_id' => 'integer',
    ];

    protected $with = ['status', 'type'];
    
    # RELATIONS

    /**
     * Get the group that owns the Submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the type that owns the Submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SubmissionType::class, 'submission_type_id');
    }

    /**
     * Get the status that owns the Submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'submission_status_id');
    }

    public function submissionStatus()
    {
        return $this->belongsTo(SubmissionStatus::class);
    }

    /**
     * Get the submitter that owns the Submission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'submitter_id');
    }

    # SCOPES

    public function scopeOfType($query, $type)
    {
        $typeId = $type;
        if (is_object($type) && get_class($type) == SubmissionType::class) {
            $typeId = $type->id;
        }
        return $query->where('submission_type_id', $typeId);
    }

    public function scopePending($query)
    {
        return $query->where('submission_status_id', config('submissions.statuses.pending'));
    }

    protected static function newFactory()
    {
        return new SubmissionFactory();
    }
}
