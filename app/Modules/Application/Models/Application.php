<?php

namespace App\Modules\Application\Models;

use DateTime;
use App\Models\Coi;
use App\Models\Cdwg;
use App\Models\EpType;
use App\Models\CoiCode;
use App\Models\HasUuid;
use App\Models\Document;
use App\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Application\Events\ContactAdded;
use App\Modules\Application\Events\DocumentAdded;
use App\Modules\Application\Events\ContactRemoved;
use App\Modules\Application\Events\DocumentReviewed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Application\Service\StepManagerFactory;
use App\Modules\Application\Events\ApplicationCompleted;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use App\Modules\Application\Events\ExpertPanelAttributesUpdated;
use App\Modules\Application\Exceptions\PersonNotContactException;
use App\Modules\Application\Exceptions\UnmetStepRequirementsException;

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
        'coi_url',
        'clingen_url',
        'name'
    ];

    // Domain methods

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
        $lastDocumentVersion = $this->getLatestVersionForDocument($document->document_type_id);
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
            Log::warning('Application::markDocumentReviewed attempted on document already marked reviewed');
            return;
        }

        $document->update(['date_reviewed' => $dateReviewed]);
        $this->touch();
        Event::dispatch(new DocumentReviewed(application: $this, document: $document));
    }

    public function completeNextAction(NextAction $nextAction, string $dateCompleted)
    {
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

    public function firstScopeDocument()
    {
        return $this->hasOne(Document::class)
            ->type(config('documents.types.scope.id'))
            ->version(1);
    }

    public function firstFinalDocument()
    {
        return $this->hasOne(Document::class)
            ->type(config('documents.types.final-app.id'))
            ->version(1);
    }

    public function logEntries()
    {
        return $this->morphMany(config('activitylog.activity_model'), 'subject');
    }

    public function latestLogEntry()
    {
        return $this->morphOne(config('activitylog.activity_model'), 'subject')
                ->where('description', 'not like', 'Added next action:%')
                ->orderBy('created_at', 'desc');
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

    public function cois()
    {
        return $this->hasMany(Coi::class);
    }

    public function cdwg()
    {
        return $this->belongsTo(Cdwg::class);
    }

    // Access methods

    public static function findByCoiCode($code)
    {
        return static::where('coi_code', $code)->first();
    }

    public static function findByCoiCodeOrFail($code)
    {
        return static::where('coi_code', $code)->sole();
    }

    public static function latestLogEntryForUuid($uuid)
    {
        return static::findByUuid($uuid)->latestLogEntry;
    }

    private function getLatestVersionForDocument($DocumentTypeId)
    {
        $results = $this->documents()
            ->where('document_type_id', $DocumentTypeId)
            ->selectRaw('max(version) as max_version')
            ->first();

        if (is_null($results) || is_null($results->max_version)) {
            return 0;
        }

        return $results->max_version;
    }

    public function getNameAttribute()
    {
        return $this->long_base_name ?? $this->working_name;
    }

    public function getClingenUrlAttribute()
    {
        if (is_null($this->affiliation_id)) {
            return null;
        }

        return 'https://clinicalgenome.org/affiliation/'.$this->affiliation_id;
    }

    public function getCoiUrlAttribute()
    {
        return '/expert-panels/'.urlencode($this->name).'/coi/'.$this->coi_code;
    }
    
    

    // Factory support
    protected static function newFactory()
    {
        return new ApplicationFactory();
    }
}
