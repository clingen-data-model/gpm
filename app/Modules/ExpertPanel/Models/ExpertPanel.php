<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\AnnualUpdate;
use App\Models\Traits\HasUuid;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use App\Models\Contracts\HasMembers;
use App\Models\Contracts\RecordsEvents;
use Illuminate\Database\Eloquent\Model;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\GroupMember;
use Database\Factories\ExpertPanelFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\RecordsEvents as TraitsRecordsEvents;
use App\Modules\ExpertPanel\Models\CurationReviewProtocol;
use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\ExpertPanel\Models\ExpertPanelType;
use App\Modules\ExpertPanel\Models\Specification;
use App\Modules\Group\Models\Contracts\BelongsToGroup;
use App\Modules\Group\Models\Traits\BelongsToGroup as TraitsBelongsToGroup;
use App\Modules\Group\Models\Traits\HasMembers as TraitsHasMembers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExpertPanel extends Model implements HasMembers, BelongsToGroup, RecordsEvents
{
    use HasFactory;
    use SoftDeletes;
    use HasUuid;
    use TraitsBelongsToGroup;
    use TraitsRecordsEvents;
    use TraitsHasMembers;

    protected $fillable = [
        'group_id',
        'expert_panel_type_id',
        'long_base_name',
        'short_base_name',
        'affiliation_id',
        'clinvar_org_id',
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
    ];

    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'expert_panel_type_id' => 'integer',
        'curation_review_protocol_id' => 'integer',
        'reanalysis_discrepency_resolution_id' => 'integer',
        'current_step' => 'integer',

        'reanalysis_conflicting' => 'boolean',
        'reanalysis_review_lp'   => 'boolean',
        'reanalysis_review_lb'   => 'boolean',
        'utilize_gt'             => 'boolean',
        'utilize_gci'            => 'boolean',
        'curations_publicly_available' => 'boolean',
        'pub_policy_reviewed'    => 'boolean',
        'draft_manuscripts'      => 'boolean',
        'recuration_process_review' => 'boolean',
        'biocurator_training'    => 'boolean',
        'biocurator_mailing_list'=> 'boolean',

        'date_initiated'            => 'datetime',
        'step_1_received_date'      => 'datetime',
        'step_1_approval_date'      => 'datetime',
        'step_2_approval_date'      => 'datetime',
        'step_3_approval_date'      => 'datetime',
        'step_4_received_date'      => 'datetime',
        'step_4_approval_date'      => 'datetime',
        'date_completed'            => 'datetime',
        'nhgri_attestation_date'    => 'datetime',
        'preprint_attestation_date' => 'datetime',
        'reanalysis_attestation_date' => 'datetime',
        'gci_training_date'         => 'datetime',
        'gcep_attestation_date'     => 'datetime',
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

    // ──────── Relationships ─────────────────────────────────────────────────────

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
            ->ofMany(['created_at' => 'max'], fn (Builder $q) => $q->pending());
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
        return $this->hasOne(AnnualUpdate::class)->ofMany(
            ['id' => 'max'],
            function (Builder $query): void {
                $query->whereHas('window', function (Builder $q): void {
                    $q->forYear(Carbon::now()->year - 2);
                });
        });
    }

    // ──────── Scopes ────────────────────────────────────────────────────────────

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('date_completed');
    }

    public function scopeApplying(Builder $query): Builder
    {
        return $query->whereNull('date_completed');
    }

    public function scopeDefinitionApproved(Builder $query): Builder
    {
        return $query->whereNotNull('step_1_approval_date');
    }

    public function scopeDraftApproved(Builder $query): Builder
    {
        return $query->whereNotNull('step_2_approval_date');
    }

    public function scopePilotApproved(Builder $query): Builder
    {
        return $query->whereNotNull('step_3_approval_date');
    }

    public function scopeSustainedCurationApproved(Builder $query): Builder
    {
        return $query->whereNotNull('step_4_approval_date');
    }

    public function scopeGroupType(Builder $query, int|object $type): Builder
    {
        $typeId = is_object($type) ? $type->id : $type;

        return $query->whereHas('group', function (Builder $q) use ($typeId): void {
            $q->where('group_type_id', $typeId);
        });
    }

    public function scopeTypeGcep(Builder $query): Builder
    {
        return $query->groupType((int) config('groups.types.gcep.id'));
    }

    public function scopeTypeVcep(Builder $query): Builder
    {
        return $query->groupType((int) config('groups.types.vcep.id'));
    }

    public function scopeTypeScvcep(Builder $query): Builder
    {
        return $query->groupType((int) config('groups.types.scvcep.id'));
    }

    // ──────── Finders / helpers ────────────────────────────────────────────────

    public static function findByAffiliationId(string|int $affiliationId): ?self
    {
        return static::where('affiliation_id', $affiliationId)->first();
    }

    public static function findByAffiliationIdOrFail(string|int $affiliationId): self
    {
        return static::where('affiliation_id', $affiliationId)->sole();
    }

    public static function latestLogEntryForUuid(string $uuid): mixed
    {
        return static::findByUuid($uuid)?->group?->latestLogEntry;
    }

    public function getLatestVersionForDocument(int $documentTypeId): int
    {
        $doc = $this->group->documents()
            ->where('document_type_id', $documentTypeId)
            ->orderBy('metadata->version', 'desc')
            ->first();

        return (int) ($doc->version ?? 0);
    }

    public function getApprovalDateForStep(int $stepNumber): ?Carbon
    {
        /** @var ?Carbon $date */
        $date = $this->{'step_' . $stepNumber . '_approval_date'} ?? null;
        return $date;
    }

    public function memberNamesForAffils(): string
    {        
        $this->loadMissing('members.person');

        return $this->members
            ->map(fn ($m) => $m->person ? trim($m->person->first_name . ' ' . $m->person->last_name) : null)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * @return array<int, array{coordinator_name:string, coordinator_email:?string}>
     */
    public function coordinatorsForAffils(): array
    {
        $this->loadMissing('members.person', 'members.roles', 'members.person.activeMemberships.roles');

        return $this->members
            ->filter(function ($m): bool {
                if ($m->relationLoaded('roles') && $m->roles instanceof Collection) {
                    return $m->roles->pluck('name')->contains('coordinator');
                }

                if ($m->person && $m->person->relationLoaded('activeMemberships')) {
                    return $m->person->activeMemberships
                        ->where('group_id', $this->group_id)
                        ->flatMap(fn ($mem) => $mem->roles ?? collect())
                        ->pluck('name')
                        ->contains('coordinator');
                }

                return false;
            })
            ->map(function ($m): array {
                $p = $m->person;
                return [
                    'coordinator_name'  => $p ? trim($p->first_name . ' ' . $p->last_name) : '',
                    'coordinator_email' => $p->email ?? null,
                ];
            })
            ->filter(fn ($row) => !empty($row['coordinator_name']))
            ->values()
            ->all();
    }

    // ──────── Accessors / Mutators (modern Attribute API) ──────────────────────

    protected function longBaseName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => [
                'long_base_name' => $this->trimEpTypeSuffix((string) $value),
            ]
        );
    }

    protected function shortBaseName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => [
                'short_base_name' => $this->trimEpTypeSuffix((string) $value),
            ]
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['long_base_name'] ?? null,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->fullLongBaseName,
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->fullLongBaseName,
        );
    }

    protected function fullLongBaseName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->addEpTypeSuffix((string) ($this->attributes['long_base_name'] ?? '')),
        );
    }

    protected function fullShortBaseName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->addEpTypeSuffix((string) ($this->attributes['short_base_name'] ?? '')),
        );
    }

    protected function clingenUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->affiliation_id
                ? 'https://clinicalgenome.org/affiliation/' . $this->affiliation_id
                : null
        );
    }

    protected function isVcep(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (int) ($this->group?->group_type_id) === (int) config('groups.types.vcep.id'),
        );
    }

    protected function isGcep(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (int) ($this->group?->group_type_id) === (int) config('groups.types.gcep.id'),
        );
    }

    protected function isScvcep(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (int) ($this->group?->group_type_id) === (int) config('groups.types.scvcep.id'),
        );
    }

    protected function definitionIsApproved(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->step_1_approval_date,
        );
    }

    protected function hasApprovedDraft(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->step_2_approval_date !== null,
        );
    }

    protected function hasApprovedPilot(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->step_3_approval_date !== null,
        );
    }

    protected function sustainedCurationIsApproved(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->step_4_approval_date !== null,
        );
    }

    // ──────── Internals ────────────────────────────────────────────────────────

    private function trimEpTypeSuffix(string $string): string
    {
        if ($string === '') {
            return '';
        }

        $s = trim($string);
        $s = preg_replace('/\s*(VCEP|GCEP|SCVCEP)$/', '', $s) ?? '';

        return $s;
    }

    private function addEpTypeSuffix(string $string): string
    {
        if (!$this->type) {
            return rtrim($string);
        }
        return rtrim($string . ' ' . $this->type->display_name);
    }

    protected static function newFactory(): ExpertPanelFactory
    {
        return new ExpertPanelFactory();
    }
}
