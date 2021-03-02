<?php

namespace App\Domain\Application\Models;

use DateTime;
use App\Models\Cdwg;
use App\Models\EpType;
use App\Models\HasUuid;
use App\Models\Document;
use App\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Domain\Person\Models\Person;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Application\Events\ContactAdded;
use App\Domain\Application\Events\StepApproved;
use App\Domain\Application\Events\DocumentAdded;
use App\Domain\Application\Events\ContactRemoved;
use App\Domain\Application\Events\NextActionAdded;
use App\Domain\Application\Events\DocumentReviewed;
use App\Domain\Application\Events\NextActionCompleted;
use App\Domain\Application\Service\StepManagerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\Application\Events\ApplicationCompleted;
use App\Domain\Application\Events\ApplicationInitiated;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use App\Domain\Application\Events\ExpertPanelAttributesUpdated;
use App\Domain\Application\Exceptions\PersonNotContactException;
use App\Domain\Application\Exceptions\UnmetStepRequirementsException;

class Application extends Model
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;

    protected $fillable = [
        'working_name',
        'long_base_name',
        'short_base_name',
        'affiliation_id',
        'cdwg_id',
    ];

    protected $dates = [
        'date_initiated',
        'date_completed'
    ];

    protected $casts = [
        'ep_type_id' => 'int',
        'cdwg_id' => 'int',
        'current_step' => 'int',
        'approval_dates'=> 'json'
    ];

    protected $appends = [
        'clingen_url',
        'name'
    ];

    // Domain methods
    public static function initiate(string $uuid, string $working_name, int $cdwg_id, int $ep_type_id, DateTime $date_initiated)
    {
        $application = new static();
        $application->uuid = $uuid;
        $application->working_name = $working_name;
        $application->cdwg_id = $cdwg_id;
        $application->ep_type_id = $ep_type_id;
        $application->date_initiated = $date_initiated;
        $application->save();
    
        Event::dispatch(new ApplicationInitiated($application));

        return $application;
    }

    public function addContact(Person $contact)
    {

        // We don't want to add the same contact twice.
        if ($this->contacts()->get()->contains($contact)) {
            return;
        }

        $this->contacts()->attach($contact);
        $this->touch();
        Event::dispatch(new ContactAdded($this, $contact));
    }

    public function removeContact(Person $contact)
    {
        if (! $this->contacts->pluck('uuid')->contains($contact->uuid)) {
            throw new PersonNotContactException($this, $contact);
        }

        $this->contacts()->detach($contact);
        $this->touch();
        Event::dispatch(new ContactRemoved($this, $contact));
    }
    
    public function addDocument(Document $document)
    {
        $lastDocumentVersion = $this->getLatestVersionForDocument($document->document_category_id);
        $document->version = $lastDocumentVersion+1;

        $this->documents()->save($document);
        $this->touch();

        $event = new DocumentAdded(
            application: $this,
            document: $document
        );
        Event::dispatch($event);
    }
        
    public function markDocumentReviewed(Document $document, Carbon $dateReviewed)
    {
        if (! is_null($document->date_reviewed)) {
            Log::warning('Applicaiton::markDocumentReviewed attempted on document already marked reviewed');
            return;
        }

        $document->update(['date_reviewed' => $dateReviewed]);
        $this->touch();
        Event::dispatch(new DocumentReviewed(application: $this, document: $document));
    }

    public function addLogEntry(string $entry, string $logDate, ?int $step = 1)
    {
        $logEntry = activity('applications')
            ->performedOn($this)
            ->createdAt(Carbon::parse($logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $entry,
                'log_date' => $logDate,
                'step' => $step
            ])->log($entry);
        $this->touch();
    }

    public function addNextAction(NextAction $nextAction)
    {
        $this->nextActions()->save($nextAction);
        $this->touch();
        
        Event::dispatch(new NextActionAdded($this, $nextAction));
    }

    public function completeNextAction(NextAction $nextAction, string $dateCompleted)
    {
        $nextAction->date_completed = $dateCompleted;
        $nextAction->save();
        $this->touch();

        Event::dispatch(new NextActionCompleted(application: $this, nextAction: $nextAction));
    }

    public function completeApplication(Carbon $dateCompleted)
    {
        $stepManager = app()->make(StepManagerFactory::class)($this);
        if ($stepManager->isLastStep()) {
            $this->date_completed = $dateCompleted;
            $this->save();
        }

        Event::dispatch(new ApplicationCompleted($this));
    }

    public function setExpertPanelAttributes(array $attributes)
    {
        $this->fill($attributes);
        if ($this->isDirty()) {
            $updatedAttributes = $this->getDirty();
            $this->save();
            Event::dispatch(new ExpertPanelAttributesUpdated($this, $updatedAttributes));
        }
    }
    
    
    public function addApprovalDate(int $step, Carbon $date)
    {
        if (is_null($this->approval_dates)) {
            $this->approval_dates = [];
        }
        $approvalDates = $this->approval_dates;
        $approvalDates['step '.$step] = $date;
        $this->approval_dates = $approvalDates;
    }

    // Relationships
    public function epType()
    {
        return $this->belongsTo(EpType::class);
    }

    public function type()
    {
        return $this->epType();
    }

    public function contacts()
    {
        return $this->belongsToMany(Person::class)->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function logEntries()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function latestLogEntry()
    {
        return $this->morphOne(Activity::class, 'subject')
                ->orderBy('created_at', 'desc')
                ;
    }

    public function nextActions()
    {
        return $this->hasMany(NextAction::class);
    }

    public function latestPendingNextAction()
    {
        return $this->hasOne(NextAction::class)
                ->pending()
                ->orderBy('created_at', 'desc');
    }

    public function cdwg()
    {
        return $this->belongsTo(Cdwg::class);
    }

    // Access methods
    static public function latestLogEntryForUuid($uuid)
    {
        return static::findByUuid($uuid)->latestLogEntry;
    }

    private function getLatestVersionForDocument($documentCategoryId)
    {
        $results = $this->documents()
            ->where('document_category_id', $documentCategoryId)
            ->selectRaw('max(version) as max_version')
            ->first();

        if (is_null($results) || is_null($results->max_version)) {
            return 0;
        }

        return $results->max_version;
    }

    public function getNameAttribute()
    {
        return $this->short_base_name ?? $this->working_name;
    }

    public function getClingenUrlAttribute()
    {
        if (is_null($this->affiliation_id)) {
            return null;
        }

        return 'https://clinicalgenome.org/affiliation/'.$this->affiliation_id;
    }
    

    // Factory support
    static protected function newFactory()
    {
        return new ApplicationFactory();
    }
}
