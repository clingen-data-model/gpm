<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\AnnualUpdate;
use App\Models\Traits\HasUuid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\Contracts\HasNotes;
use App\Modules\Group\Models\Group;
use App\Models\Contracts\HasMembers;
use App\Models\Contracts\RecordsEvents;
use Illuminate\Database\Eloquent\Model;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use Database\Factories\ExpertPanelFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Models\Traits\HasNotes as TraitsHasNotes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\ExpertPanel\Models\ExpertPanelType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Group\Models\Contracts\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use App\Modules\ExpertPanel\Models\CurationReviewProtocol;
use App\Models\Traits\RecordsEvents as TraitsRecordsEvents;
use App\Modules\Group\Models\Traits\HasMembers as TraitsHasMembers;
use App\Modules\Group\Models\Traits\BelongsToGroup as TraitsBelongsToGroup;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ExpertPanel extends Model implements HasNotes, HasMembers, BelongsToGroup, RecordsEvents
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;
    use TraitsHasNotes;
    use TraitsBelongsToGroup;
    use TraitsRecordsEvents;
    use TraitsHasMembers;

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
        'membership_description',
        'scope_description',
        'nhgri_attestation_date',
        'preprint_attestation_date',
        'curation_review_protocol_id',
        'curation_review_protocol_other',
        'curation_review_process_notes',
        'meeting_frequency',
        'reanalysis_conflicting',
        'reanalysis_review_lp',
        'reanalysis_review_lb',
        'reanalysis_other',
        'reanalysis_attestation_date',

        'utilize_gt',
        'utilize_gci',
        'curations_publicly_available',
        'pub_policy_reviewed',
        'draft_manuscripts',
        'recuration_process_review',
        'biocurator_training',
        'gci_training_date',
        'biocurator_mailing_list',

        'cdwg_id',
        // 'working_name',
    ];

    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'expert_panel_type_id' => 'integer',
        'curation_review_protocol_id' => 'integer',
        'reanalysis_discrepency_resolution_id' => 'integer',
        'current_step' => 'integer',
        'reanalysis_conflicting' => 'boolean',
        'reanalysis_review_lp' => 'boolean',
        'reanalysis_review_lb' => 'boolean',
        'utilize_gt' => 'boolean',
        'utilize_gci' => 'boolean',
        'curations_publicly_available' => 'boolean',
        'pub_policy_reviewed' => 'boolean',
        'draft_manuscripts' => 'boolean',
        'recuration_process_review' => 'boolean',
        'biocurator_training' => 'boolean',
        'biocurator_mailing_list' => 'boolean',
        'date_initiated' => 'datetime',
        'step_1_received_date' => 'datetime',
        'step_1_approval_date' => 'datetime',
        'step_2_approval_date' => 'datetime',
        'step_3_approval_date' => 'datetime',
        'step_4_received_date' => 'datetime',
        'step_4_approval_date' => 'datetime',
        'date_completed' => 'datetime',
        'nhgri_attestation_date' => 'datetime',
        'preprint_attestation_date' => 'datetime',
        'reanalysis_attestation_date' => 'datetime',
        'gci_training_date' => 'datetime',
        'gcep_attestation_date' => 'datetime',
    ];

    protected $with = [
        'type',
    ];

    protected $appends = [
        'name',
        'full_long_base_name',
        'full_short_base_name',
        'full_name',
        'display_name',
        'is_vcep',
        'is_gcep',
        'definition_is_approved',
        'has_approved_draft',
        'has_approved_pilot',
        'sustained_curation_is_approved',
    ];

    protected static function booted(): void
    {
        static::deleted(function (self $expertPanel): void {
            $expertPanel->evidenceSummaries->each->delete();
            $expertPanel->specifications->each->delete();
        });
    }

    // RELATIONSHIPS
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExpertPanelType::class, 'expert_panel_type_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id')->contact();
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id');
    }

    public function nextActions(): HasMany
    {
        return $this->hasMany(NextAction::class);
    }

    public function latestPendingNextAction(): HasOne
    {
        return $this->hasOne(NextAction::class)
            ->pending()
            ->orderBy('created_at', 'desc');
    }

    public function genes(): HasMany
    {
        return $this->hasMany(Gene::class);
    }

    public function evidenceSummaries(): HasMany
    {
        return $this->hasMany(EvidenceSummary::class);
    }

    public function cdwg(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'cdwg_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(Specification::class);
    }

    public function curationReviewProtocol(): BelongsTo
    {
        return $this->belongsTo(CurationReviewProtocol::class);
    }

    public function annualUpdates(): HasMany
    {
        return $this->hasMany(AnnualUpdate::class);
    }

    public function latestAnnualUpdate(): HasOne
    {
        return $this->hasOne(AnnualUpdate::class)->latestOfMany();
    }

    public function previousYearAnnualUpdate(): HasOne
    {
        return $this->hasOne(AnnualUpdate::class)->ofMany([], function ($query) {
            $query->whereHas('window', function ($q) {
                $q->forYear(Carbon::now()->year - 2);
            });
        });
    }

    // SCOPES
    public function scopeApproved($query)
    {
        return $query->whereNotNull('date_completed');
    }

    public function scopeApplying($query)
    {
        return $query->whereNull('date_completed');
    }

    public function scopeDefinitionApproved($query)
    {
        return $query->whereNotNull('step_1_approval_date');
    }

    public function scopeDraftApproved($query)
    {
        return $query->whereNotNull('step_2_approval_date');
    }

    public function scopePilotApproved($query)
    {
        return $query->whereNotNull('step_3_approval_date');
    }

    public function scopeSustainedCurationApproved($query)
    {
        return $query->whereNotNull('step_4_approval_date');
    }

    public function scopeGroupType($query, $type)
    {
        $typeId = is_object($type) ? $type->id : $type;

        return $query->whereHas('group', function ($query) use ($typeId) {
            $query->where('group_type_id', $typeId);
        });
    }

    public function scopeTypeGcep($query)
    {
        return $query->groupType(config('groups.types.gcep.id'));
    }

    public function scopeTypeVcep($query)
    {
        return $query->groupType(config('groups.types.vcep.id'));
    }

    public function scopeTypeScvcep($query)
    {
        return $query->groupType(config('groups.types.scvcep.id'));
    }


    public static function findByAffiliationId($affiliationId): ?self
    {
        return static::where('affiliation_id', $affiliationId)->first();
    }

    public static function findByAffiliationIdOrFail($affiliationId): self
    {
        return static::where('affiliation_id', $affiliationId)->sole();
    }

    public static function latestLogEntryForUuid($uuid)
    {
        return static::findByUuid($uuid)->group->latestLogEntry;
    }

    public function getLatestVersionForDocument($DocumentTypeId)
    {
        $results = $this->group->documents()
            ->where('document_type_id', $DocumentTypeId)
            ->orderBy('metadata->version', 'desc')
            ->first();

        if (is_null($results) || is_null($results->version)) {
            return 0;
        }

        return $results->version;
    }

    // ACCESSORS
    public function getFirstScopeDocumentAttribute()
    {
        return $this->group->documents()
            ->type(config('documents.types.scope.id'))
            ->isVersion(1)
            ->first();
    }

    public function getFirstFinalDocumentAttribute()
    {
        return $this->group->documents()
            ->type(config('documents.types.final-app.id'))
            ->isVersion(1)
            ->first();
    }

    public function isVcep(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->group?->group_type_id) === (int) config('groups.types.vcep.id'),
        );
    }

    public function isGcep(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->group?->group_type_id) === (int) config('groups.types.gcep.id'),
        );
    }

    public function isScvcep(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->group?->group_type_id) === (int) config('groups.types.scvcep.id'),
        );
    }

    public function getNameAttribute()
    {
        return $this->long_base_name;
    }

    public function getFullNameAttribute()
    {
        return $this->full_long_base_name;
    }

    public function getDisplayNameAttribute()
    {
        return $this->getFullLongBaseNameAttribute();
    }

    public function getFullLongBaseNameAttribute()
    {
        return isset($this->attributes['long_base_name'])
            ? $this->addEpTypeSuffix($this->attributes['long_base_name'])
            : $this->addEpTypeSuffix('');
    }

    public function getFullShortBaseNameAttribute()
    {
        return isset($this->attributes['short_base_name'])
            ? $this->addEpTypeSuffix($this->attributes['short_base_name'])
            : $this->addEpTypeSuffix('');
    }

    public function setLongBaseNameAttribute($value): void
    {
        $this->attributes['long_base_name'] = $this->trimEpTypeSuffix($value);
    }

    public function setShortBaseNameAttribute($value): void
    {
        $this->attributes['short_base_name'] = $this->trimEpTypeSuffix($value);
    }

    private function trimEpTypeSuffix($string): string
    {
        if (in_array(substr($string, -4), ['VCEP', 'GCEP'])) {
            return trim(substr($string, 0, -4));
        }
        return $string;
    }

    private function addEpTypeSuffix($string): string
    {
        if (!$this->type) {
            return $string;
        }
        return $string . ' ' . $this->type->display_name;
    }

    public function getClingenUrlAttribute(): ?string
    {
        if (is_null($this->affiliation_id)) {
            return null;
        }

        return 'https://clinicalgenome.org/affiliation/' . $this->affiliation_id;
    }

    public function getDefinitionIsApprovedAttribute(): bool
    {
        return (bool) $this->step_1_approval_date;
    }

    public function getHasApprovedDraftAttribute(): bool
    {
        return !is_null($this->step_2_approval_date);
    }

    public function getHasApprovedPilotAttribute(): bool
    {
        return !is_null($this->step_3_approval_date);
    }

    public function getSustainedCurationIsApprovedAttribute(): bool
    {
        return !is_null($this->step_4_approval_date);
    }

    public function getApprovalDateForStep($stepNumber): ?Carbon
    {
        /** @var ?Carbon $date */
        $date = $this->{'step_' . $stepNumber . '_approval_date'};
        return $date;
    }

    public function memberNamesForAffils(): string
    {
        // Avoid N+1s if caller didn’t eager-load.
        $this->loadMissing('members.person');

        return $this->members
            ->map(fn ($m) => $m->person ? trim($m->person->first_name . ' ' . $m->person->last_name) : null)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function coordinatorsForAffils(): array
    {
        // Try to use roles on the specific membership; fall back to person->activeMemberships scope.
        $this->loadMissing('members.person', 'members.roles', 'members.person.activeMemberships.roles');

        return $this->members
            ->filter(function ($m) {
                // Prefer checking roles on THIS membership (most accurate).
                if ($m->relationLoaded('roles') && $m->roles instanceof Collection) {
                    return $m->roles->pluck('name')->contains('coordinator');
                }

                // Fallback: check if the person is a coordinator for THIS group via activeMemberships.
                if ($m->person && $m->person->relationLoaded('activeMemberships')) {
                    return $m->person->activeMemberships
                        ->where('group_id', $this->group_id)
                        ->flatMap(fn ($mem) => $mem->roles ?? collect())
                        ->pluck('name')
                        ->contains('coordinator');
                }

                return false;
            })
            ->map(function ($m) {
                $p = $m->person;
                return [
                    'coordinator_name'  => $p ? trim($p->first_name . ' ' . $p->last_name) : null,
                    'coordinator_email' => $p->email ?? null,
                ];
            })
            ->filter(fn ($row) => !empty($row['coordinator_name']))
            ->values()
            ->all();
    }

    // Factory support
    protected static function newFactory()
    {
        return new ExpertPanelFactory();
    }
}
