<?php

namespace App\Modules\Group\Models;

use App\Modules\Person\Models\Person;
use Carbon\Carbon;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'submission_type_id',
        'submission_status_id',
        'data',
        'sent_to_chairs_at',
        'notes_for_chairs',
        'closed_at',
        'notes',
        'response_content',
        'submitter_id',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'submission_type_id' => 'integer',
        'submission_status_id' => 'integer',
        'data' => 'array',
        'closed_at' => 'datetime',
        'sent_to_chairs_at' => 'datetime',
        'submitter_id' => 'integer',
    ];

    protected $with = ['status', 'type'];

    protected $appends = ['isPending', 'is_pending'];

    // RELATIONS

    /**
     * Get the group that owns the Submission
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the type that owns the Submission
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SubmissionType::class, 'submission_type_id');
    }

    /**
     * Get the status that owns the Submission
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
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'submitter_id');
    }

    /**
     * Get all of the judgements for the Submission
     */
    public function judgements(): HasMany
    {
        return $this->hasMany(Judgement::class);
    }

    // SCOPES

    public function scopeWithStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('submission_status_id', $status);
        }

        return $query->where('submission_status_id', $status);
    }

    public function scopeOfType($query, $type)
    {
        $typeId = $type;
        if (is_object($type) && get_class($type) == SubmissionType::class) {
            $typeId = $type->id;
        }
        if (is_array($type)) {
            return $query->whereIn('submission_type_id', $type);
        }

        return $query->where('submission_type_id', $typeId);
    }

    public function scopePending($query)
    {
        return $query->whereIn(
            'submission_status_id',
            [
                config('submissions.statuses.pending.id'),
                config('submissions.statuses.under-chair-review.id'),
            ]
        );
    }

    public function scopeSentToChair($query)
    {
        return $query->whereNotNull('sent_to_chairs_at')
            ->pending();
    }

    // ACCESSORS
    public function getIsPendingAttribute()
    {
        $pendingStatuses = [
            config('submissions.statuses.pending.id'),
            config('submissions.statuses.under-chair-review.id'),
        ];

        return in_array($this->submission_status_id, $pendingStatuses);
    }

    public function getIsUnderChairReviewAttribute(): bool
    {
        return $this->submission_status_id == config('submissions.statuses.under-chair-review.id');
    }

    /**
     * DOMAIN
     */
    public function reject(string $responseContent = null): Submission
    {
        $this->update([
            'submission_status_id' => config('submissions.statuses.revisions-requested.id'),
            'response_content' => $responseContent,
            'closed_at' => Carbon::now(),
        ]);

        return $this;
    }

    protected static function newFactory()
    {
        return new SubmissionFactory();
    }
}
