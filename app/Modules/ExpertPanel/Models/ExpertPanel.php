<?php

namespace App\Modules\ExpertPanel\Models;

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
use Database\Factories\ExpertPanelFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Events\ContactAdded;
use App\Modules\ExpertPanel\Events\DocumentAdded;
use App\Modules\ExpertPanel\Events\ContactRemoved;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use App\Modules\ExpertPanel\Events\ApplicationAttributesUpdated;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;
use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;
use App\Modules\ExpertPanel\Exceptions\UnmetStepRequirementsException;

class ExpertPanel extends Model
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;

    protected $table = 'applications';

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

    protected $with = [
        'epType'
    ];

    // Domain methods
    
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
        return $this->belongsToMany(Person::class, 'application_person', 'application_id')->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'application_id');
    }

    public function firstScopeDocument()
    {
        return $this->hasOne(Document::class, 'application_id')
            ->type(config('documents.types.scope.id'))
            ->version(1);
    }

    public function firstFinalDocument()
    {
        return $this->hasOne(Document::class, 'application_id')
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
        return $this->hasMany(NextAction::class, 'application_id');
    }

    public function latestPendingNextAction()
    {
        return $this->hasOne(NextAction::class)
                ->pending()
                ->orderBy('created_at', 'desc');
    }

    public function cois()
    {
        return $this->hasMany(Coi::class, 'application_id');
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

    public function getLatestVersionForDocument($DocumentTypeId)
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

    public function setWorkingNameAttribute($value)
    {
        $this->attributes['working_name'] = $this->trimEpTypeSuffix($value);
    }
    
    public function getWorkingNameAttribute()
    {
        return $this->addEpTypeSuffix($this->attributes['working_name']);
    }
    

    public function getLongBaseNameAttribute()
    {
        return isset($this->attributes['long_base_name'])
            ? $this->addEpTypeSuffix($this->attributes['long_base_name'])
            : null;
    }

    public function getShortBaseNameAttribute()
    {
        return isset($this->attributes['short_base_name'])
            ? $this->addEpTypeSuffix($this->attributes['short_base_name'])
            : null;
    }
    
    public function setLongBaseNameAttribute($value)
    {
        $this->attributes['long_base_name'] = $this->trimEpTypeSuffix($value);
    }

    public function setShortBaseNameAttribute($value)
    {
        $this->attributes['short_base_name'] = $this->trimEpTypeSuffix($value);
    }

    private function trimEpTypeSuffix($string)
    {
        if (in_array(substr($string, -4), ['VCEP', 'GCEP'])) {
            return trim(substr($string, 0, -4));
        }
        return $string;
    }

    private function addEpTypeSuffix($string)
    {
        return $string.' '.$this->epType->display_name;
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
        return new ExpertPanelFactory();
    }
}
