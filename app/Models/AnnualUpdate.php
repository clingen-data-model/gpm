<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Support\Facades\Validator;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AnnualUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_panel_id',
        'submitter_id',
        'data',
        'completed_at'
    ];

    protected $casts = [
        'id' => 'int',
        'expert_panel_id' => 'int',
        'submitter_id' => 'int',
        'data' => 'array',
        'completed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (is_null($model->annual_update_window_id)) {
                $model->annual_update_window_id = AnnualUpdateWindow::orderBy('start', 'desc')->first()->id;
            }
        });
    }

    /**
     * RELATIONS
     */

    /**
     * Get the expertPanel that owns the AnnualUpdate
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel(): BelongsTo
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    /**
     * Get the window that owns the AnnualUpdate
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function window(): BelongsTo
    {
        return $this->belongsTo(AnnualUpdateWindow::class, 'annual_update_window_id');
    }

    /**
     * Get the submitter that owns the AnnualUpdate
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'submitter_id');
    }

    public function getMembers()
    {
        return $this->expertPanel->group->members;
    }

    /**
     * SCOPES
     */

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeForWindow($query, $windowId)
    {
        return $query->where('annual_update_window_id', $windowId);
    }


    /**
     * ACCESSORS
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getForYearAttribute()
    {
        return $this->created_at->year - 1;
    }

    public function getIsGcepAttribute()
    {
        return $this->expertPanel->group->isGcep;
    }

    public function getIsVcepAttribute()
    {
        return $this->expertPanel->group->isVcep;
    }


    public function toCsvArray(): array
    {
        $result = [
                'record_id' => $this->id,
                'expert_panel' => $this->expertPanel->displayName,
                'submitter_name' => $this->submitter
                    ? $this->submitter->person->name
                    : null,
                'submitter_email' => $this->submitter
                    ? $this->submitter->person->email
                    : null,
                'grant' => $this->getDataOrNull('grant'),
                'submitted_inactive_form' => $this->getDataOrNull('submitted_inactive_form'),
                'membership_attestation' => $this->getDataOrNull('membership_attestation'),
                'applied_for_funding' => $this->getDataOrNull('applied_for_funding'),
                'funding' => $this->getDataOrNull('funding'),
                'funding_other_details' => $this->getDataOrNull('funding_other_details'),
                'funding_thoughts' => $this->getDataOrNull('funding_thoughts'),
                'website_attestation' => $this->getDataOrNull('website_attestation'),
                'ongoing_plans_updated' => $this->getDataOrNull('ongoing_plans_updated'),
                'ongoing_plans_update_details' => $this->getDataOrNull('ongoing_plans_update_details'),
                'goals' => $this->getDataOrNull('goals'),
                'cochair_commitment' => $this->getDataOrNull('cochair_commitment'),
                'cochair_commitment_details' => $this->getDataOrNull('cochair_commitment_details'),
                'expert_panels_change' => $this->getDataOrNull('expert_panels_change'),
              //  'long_term_chairs' => $this->getDataOrNull('long_term_chairs'),
        ];

        if ($this->isGcep) {
            $result = array_merge(
                $result,
                [
                    'ep_activity' => $this->getDataOrNull('ep_activity'),
                    'gci_use' => $this->getDataOrNull('gci_use'),
                    'gci_use_details' => $this->getDataOrNull('gci_use_details'),
                    'gt_gene_list' => $this->getDataOrNull('gt_gene_list'),
                    'gt_gene_list_details' => $this->getDataOrNull('gt_gene_list_details'),
                    'gt_precuration_info' => $this->getDataOrNull('gt_precuration_info'),
                    'gt_precuration_info_details' => $this->getDataOrNull('gt_precuration_info_details'),
                    'published_count' => $this->getDataOrNull('published_count'),
                    'approved_unpublished_count' => $this->getDataOrNull('approved_unpublished_count'),
                    'in_progress_count' => $this->getDataOrNull('in_progress_count'),
                    'recuration_begun' => $this->getDataOrNull('recuration_begun'),
                    'recuration_designees' => $this->getDataOrNull('recuration_designees'),
                    'system_discrepancies' => $this->getDataOrNull('system_discrepancies'),
                    'publishing_issues' => $this->getDataOrNull('publishing_issues'),
                ]
            );
        }

        if ($this->isVcep) {
            $variantCounts = $this->getDataOrNull('variant_counts');
            if (!is_null($variantCounts)) {
                $variantCountString = $this->getVariantCountString($variantCounts);
            }

            $result = array_merge(
                $result,
                [
                    'vci_use' => $this->getDataOrNull('vci_use'),
                    'vci_use_details' => $this->getDataOrNull('vci_use_details'),
                    'cochair_commitment' => $this->getDataOrNull('cochair_commitment'),
                    'cochair_commitment_details' => $this->getDataOrNull('cochair_commitment_details'),
                    'sepcification_progress' => $this->getDataOrNull('sepcification_progress'),
                    'specification_url' => $this->getDataOrNull('specification_url'),
                 //   'variant_counts' => $variantCountString,
                    'variant_workflow_changes' => $this->getDataOrNull('variant_workflow_changes'),
                    'variant_workflow_changes_details' => $this->getDataOrNull('variant_workflow_changes_details'),
                    //'specification_progress' => $this->getDataOrNull('specification_progress'),
                   // 'specification_progress_url' => $this->getDataOrNull('specification_progress_url'),
                    'specification_plans' => $this->getDataOrNull('specification_plans'),
                    'specification_plans_details' => $this->getDataOrNull('specification_plans_details'),
                    'rereview_discrepencies_progress' => $this->getDataOrNull('rereview_discrepencies_progress'),
                    'rereview_lp_and_vus_progress' => $this->getDataOrNull('rereview_lp_and_vus_progress'),
                    'rereview_lb_progress' => $this->getDataOrNull('rereview_lb_progress'),
                    'member_designation_changed' => $this->getDataOrNull('member_designation_changed'),
                    //'specification_progress_details' => $this->getDataOrNull('specification_progress_details'),
                    'specifications_for_new_gene' => $this->getDataOrNull('specifications_for_new_gene'),
                    'specifications_for_new_gene_details' => $this->getDataOrNull('specifications_for_new_gene_details'),
                    'submit_clinvar' => $this->getDataOrNull('submit_clinvar'),
                    'vcep_publishing_issues' => $this->getDataOrNull('vcep_publishing_issues'),
                    'rereview_discrepancies_progress' => $this->getDataOrNull('rereview_discrepancies_progress'),
                    'changes_to_call_frequency' => $this->getDataOrNull('changes_to_call_frequency'),
                    'member_designation_changed'  => $this->getDataOrNull('member_designation_changed'),
                ],
            );
        }

        return $result;
    }

    public function loadForUse()
    {
        $this->load([
            'expertPanel' => function ($query) {
                $query->select(['id', 'expert_panel_type_id', 'long_base_name', 'group_id', 'affiliation_id', 'step_1_approval_date','step_2_approval_date','step_3_approval_date','step_4_approval_date']);
            },
            'expertPanel.group' => function ($query) {
                $query->select(['id', 'group_type_id', 'name', 'uuid']);
            },
            'expertPanel.group.members',
            'expertPanel.group.members.person',
            'expertPanel.group.members.person' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email');
            },
            'expertPanel.group.coordinators',
            'expertPanel.group.coordinators.person',
            'expertPanel.previousYearAnnualUpdate' => function ($query) {
                $query->select('id', 'annual_updates.expert_panel_id', 'annual_update_window_id');
            },
            'expertPanel.previousYearAnnualUpdate.window',
            'submitter' => function ($query) {
                $query->select(['id', 'person_id']);
            },
            'submitter.person' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email');
            },
            'window',
        ]);

        return $this;
    }

    private function getVariantCountString($variantCounts): String
    {
        $string = '';
        foreach ($variantCounts as $gene) {
            $string .= $gene['gene_symbol']
                        .' - in_clinvar: '
                            .(isset($gene['in_clinvar']) ? $gene['in_clinvar'] : 0)
                        .', gci_approved: '
                            .(isset($gene['gci_approved']) ? $gene['gci_approved'] : 0)
                        .', provisionally_approved: '
                            .(isset($gene['provisionally_approved']) ? $gene['provisionally_approved'] : 0)
                        .'; ';
        }

        return $string;
    }


    private function getDataOrNull($key)
    {
        if (!$this->data) {
            return null;
        }
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }
}
