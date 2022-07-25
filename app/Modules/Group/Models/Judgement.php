<?php

namespace App\Modules\Group\Models;

use InvalidArgumentException;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Judgement extends Model
{
    use HasFactory;

    public $fillable = [
        'decision',
        'notes',
        'submission_id',
        'person_id'
    ];

    public $casts = [
        'id' => 'integer',
        'submission_id' => 'integer',
        'person_id' => 'integer',
    ];

    // RELATIONSHIPS
    /**
     * Get the submission that owns the Judgement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
    

    // SCOPES
    public function scopeForSubmission($query, $submission)
    {
        if (is_iterable($submission)) {
            return $query->whereIn('submission_id', $submission);
        }

        $submissionId = $submission;
        if (is_object($submission) && $submission instanceof Model) {
            $submissionId = $submission->id;
        }

        return $query->where('submission_id', $submissionId);
    }
   
    public function scopeByPerson($query, $person)
    {
        if (is_iterable($person)) {
            return $query->whereIn('person_id', $person);
        }

        $personId = $person;
        if (is_object($person) && $person instanceof Model) {
            $personId = $person->id;
        }

        return $query->where('person_id', $personId);
    }
    

    // MUTATORS
    public function setDecisionAttribute($value)
    {
        if (!in_array($value, config('submissions.decisions'))) {
            throw new InvalidArgumentException('Cannot set decision attribute. '. $value .' not found in '.implode(', ', config('submissions.decisions')));
        }
        $this->attributes['decision'] = $value;
    }
    
}
