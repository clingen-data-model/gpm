<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\Cdwg;
use App\Models\HasUuid;
use App\Models\Document;
use Illuminate\Support\Carbon;
use App\Models\Contracts\HasNotes;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Models\Contracts\HasMembers;
use App\Modules\Person\Models\Person;
use App\Models\Contracts\HasDocuments;
use App\Models\Contracts\RecordsEvents;
use App\Modules\ExpertPanel\Models\Coi;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Database\Factories\ExpertPanelFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Models\Traits\HasNotes as TraitsHasNotes;
use App\Modules\ExpertPanel\Models\ExpertPanelType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Modules\Group\Models\Contracts\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\ExpertPanel\Models\SpecificationRuleSet;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use App\Models\Traits\HasDocuments as TraitsHasDocuments;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Traits\RecordsEvents as TraitsRecordsEvents;
use App\Modules\Group\Models\Traits\HasMembers as TraitsHasMembers;
use App\Modules\Group\Models\Traits\BelongsToGroup as TraitsBelongsToGroup;

class ExpertPanel extends Model implements HasNotes, HasMembers, BelongsToGroup, RecordsEvents, HasDocuments
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;
    use TraitsHasNotes;
    use TraitsBelongsToGroup;
    use TraitsRecordsEvents;
    // use TraitsHasDocuments;
    use TraitsHasMembers;

    // protected $table = 'applications';

    protected $fillable = [
        'group_id',
        'expert_panel_type_id',
        'long_base_name',
        'short_base_name',
        'affiliation_id',
        'date_initiated',
        'step_1_received_date',
        'step_1_approval_date',
        'step_2_approval_date',
        'step_3_approval_date',
        'step_4_received_date',
        'step_4_approval_date',
        'date_completed',
        'hypothesis_group',
        'member_description',
        'scope_description',
        'coi_code',
        'nhgri_attestation_date',
        'preprint_attestation_date',
        'curtion_review_protocol',
        'curation_review_protocol_other',
        'reanalysis_discrepency_resolution_id',
        'cdwg_id',
        'working_name',
    ];

    protected $dates = [
        'date_initiated',
        'step_1_received_date',
        'step_1_approval_date',
        'step_2_approval_date',
        'step_3_approval_date',
        'step_4_received_date',
        'step_4_approval_date',
        'date_completed',
        'nhgri_attestation_date',
        'preprint_attestation_date',
    ];

    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'expert_panel_type_id' => 'integer',
        'curtion_review_protocol' => 'integer',
        'reanalysis_discrepency_resolution_id' => 'integer',
        'current_step' => 'integer'
    ];

    protected $with = [
        'type',
        'group'
    ];

    protected $appends = [
        'working_name',
        'name',
        'coi_url'
    ];

    // Domain methods
    
    public function setApprovalDate(int $step, string $date)
    {
        $approvalDateAttribute = 'step_'.$step.'_approval_date';
        $this->{$approvalDateAttribute} = $date;
    }

    public function addContact(Person $person)
    {
        $member = GroupMember::firstOrCreate([
            'person_id' => $person->id,
            'group_id' => $this->group_id,
            'is_contact' => 1
        ]);
        $this->touch();
    }


    // Relationships
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function expertPanelType()
    {
        return $this->belongsTo(ExpertPanelType::class);
    }

    public function epType()
    {
        return $this->expertPanelType();
    }

    public function type()
    {
        return $this->expertPanelType();
    }

    public function contacts()
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id')
            ->contact();
    }

    public function documents(): MorphMany
    {
        $this->ensureGroupLoaded();
        return $this->group->documents();
    }

    public function getFirstScopeDocumentAttribute()
    {
        return $this->documents()
                ->type(config('documents.types.scope.id'))
                ->isVersion(1)
                ->first()
            ;
        ;
    }

    public function getFirstFinalDocumentAttribute()
    {
        return $this->documents()
            ->type(config('documents.types.final-app.id'))
            ->isVersion(1)
            ->first();
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
        return $this->hasMany(Coi::class, 'expert_panel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function genes()
    {
        return $this->hasMany(\App\Modules\ExpertPanels\Models\Gene::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evidenceSummaries()
    {
        return $this->hasMany(\App\Modules\ExpertPanels\Models\EvidenceSummaries::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function leadershipAttestation()
    {
        return $this->hasOne(\App\Modules\ExpertPanels\Models\LeadershipAttestation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function biocuratorOnboardingAttestation()
    {
        return $this->hasOne(\App\Modules\ExpertPanels\Models\BiocuratorOnboardingAttestation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curtionReviewProtocol()
    {
        return $this->belongsTo(\App\Modules\ExpertPanels\Models\CurtionReviewProtocol::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reanalysisDiscrepencyResolution()
    {
        return $this->belongsTo(\App\Modules\ExpertPanels\Models\ReanalysisDiscrepencyResolutions::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(GroupType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentGroup(): BelongsTo
    {
        $this->ensureGroupLoaded();
        return $this->group->parent();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cdwg(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'cdwg_id');
    }

    /**
     * Get all of the Members for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function members(): Relation
    {
        $this->ensureGroupLoaded();
        return $this->group->members();
    }
    
    /**
     * Get all of the Members for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function biocurators(): Relation
    {
        return $this->members()
            ->role('biocurator');
    }

    /**
     * Get all of the Specifications for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Specifications(): HasMany
    {
        return $this->hasMany(Specification::class);
    }

    /**
     * Get all of the specifications for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function specificationRuleSets(): HasManyThrough
    {
        return $this->hasManyThrough(SpecificationRuleSet::class, Specification::class);
    }

    /**
     * Get all of the documents for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function documentsNew(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, Group::class);
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
            ->orderBy('metadata->version', 'desc')
            ->first();

        if (is_null($results) || is_null($results->version)) {
            return 0;
        }

        return $results->version;
    }

    public function getNameAttribute()
    {
        if (!$this->relationLoaded('group')) {
            $this->load('group');
        }
        return $this->long_base_name ?? $this->working_name;
    }

    public function setWorkingNameAttribute($value)
    {
        $this->ensureGroupLoaded();
        $this->group->name = $this->trimEpTypeSuffix($value);
    }
    
    public function getWorkingNameAttribute()
    {
        $this->ensureGroupLoaded();
        return $this->addEpTypeSuffix($this->group->name);
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

    private function ensureGroupLoaded()
    {
        if (!$this->relationLoaded('group')) {
            $this->load('group');
            return;
        }
    }
}
