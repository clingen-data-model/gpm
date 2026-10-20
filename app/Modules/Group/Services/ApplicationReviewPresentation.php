<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Submission;
use Illuminate\Support\Arr;

/** Presentation of captured data only; this service never loads live relationships. */
class ApplicationReviewPresentation
{
    public function step(Submission $submission): int
    {
        return (int) data_get($submission->data, 'approval_step', data_get($submission->data, 'step',
            (int) $submission->submission_type_id === (int) config('submissions.types.application.sustained-curation.id') ? 4 : 1));
    }

    public function type(array $snapshot): ?string
    {
        $id = data_get($snapshot, 'attributes.group_type_id');
        foreach (['gcep', 'vcep', 'scvcep'] as $type) {
            if ($id !== null && (int) $id === (int) config("groups.types.$type.id")) return $type;
        }
        return data_get($snapshot, 'review_context.group_type');
    }

    public function stepName(int $step): string
    {
        return $step === 4 ? 'Sustained Curation' : 'Scope and Membership';
    }

    public function sections(array $snapshot, int $step): array
    {
        $type = $this->type($snapshot);
        $ep = 'relations.expertPanel.attributes.';
        $paths = $step === 4 ? [] : [
            'Panel name' => 'attributes.name', 'Website description' => 'attributes.description',
            'Long name' => $ep.'long_base_name', 'Short name' => $ep.'short_base_name',
            'Description of scope' => $ep.'scope_description',
            'CDWG' => 'relations.parent.attributes.name',
        ];
        if ($step === 1 && in_array($type, ['vcep', 'scvcep'], true)) $paths['Membership description'] = $ep.'membership_description';
        if ($type === 'gcep' && $step === 1) {
            $paths += ['Prioritization rationale' => $ep.'prioritization_rationale',
                'Selected curation and review protocol' => 'relations.expertPanel.relations.curationReviewProtocol.attributes.full_name',
                'Other protocol details' => $ep.'curation_review_protocol_other',
                'GCEP Attestation: Utilize GeneTracker' => $ep.'utilize_gt',
                'GCEP Attestation: Utilize Gene Curation Interface' => $ep.'utilize_gci',
                'GCEP Attestation: Curations publicly available' => $ep.'curations_publicly_available',
                'GCEP Attestation: Publication policy reviewed' => $ep.'pub_policy_reviewed',
                'GCEP Attestation: Draft manuscripts submitted for review' => $ep.'draft_manuscripts',
                'GCEP Attestation: Gene-Disease Validity Recuration Process reviewed' => $ep.'recuration_process_review',
                'GCEP Attestation: Biocurator training plan' => $ep.'biocurator_training',
                'GCEP Attestation: Biocurator WG mailing list' => $ep.'biocurator_mailing_list',
                'GCEP Attestation Signed' => $ep.'gcep_attestation_date', 'GCI Training Date' => $ep.'gci_training_date'];
        }
        if ($step === 1 && in_array($type, ['vcep', 'scvcep'], true)) {
            $prefix = $type === 'scvcep' ? 'SC-VCEP' : 'VCEP';
            $paths += [
                "$prefix reassessment of conflicting ClinVar assertions" => $ep.'reanalysis_conflicting',
                ($type === 'scvcep' ? 'Re-review Likely Oncogenic and VUS classifications' : 'Re-review LP and VUS classifications') => $ep.'reanalysis_review_lp',
                'Re-review LB classifications' => $ep.'reanalysis_review_lb',
                'Reanalysis & Discrepancy Resolution — Explain differences' => $ep.'reanalysis_other',
                'Reanalysis and Discrepancy Resolution Attestation Signed' => $ep.'reanalysis_attestation_date',
            ];
        }
        if ($step === 1 && $type) $paths['NHGRI Attestation Signed'] = $ep.'nhgri_attestation_date';
        if ($step === 4 && $type !== 'gcep') {
            $prefix = $type === 'scvcep' ? 'SC-VCEP' : ($type === 'vcep' ? 'VCEP' : 'Expert Panel');
            $paths = ["$prefix Standardized Review Process" => 'relations.expertPanel.relations.curationReviewProtocol.attributes.full_name',
                'Curation and Review Process Notes' => $ep.'curation_review_process_notes'];
        }
        if ($step === 4 && $type === 'vcep') $paths['Meeting/call frequency'] = $ep.'meeting_frequency';
        $sections = [];
        foreach ($paths as $label => $path) {
            $available = Arr::has($snapshot, $path);
            $value = data_get($snapshot, $path);
            if (str_ends_with($path, 'curationReviewProtocol.attributes.full_name')) {
                // Older snapshots captured the ID but not the relation. Do not consult today's protocol table.
                if (!$available && Arr::has($snapshot, $ep.'curation_review_protocol_id')) {
                    $available = true;
                    $id = data_get($snapshot, $ep.'curation_review_protocol_id');
                    $value = $id === null ? null : 'Protocol '.$id;
                }
                if (in_array($type, ['vcep', 'scvcep'], true) && Arr::has($snapshot, $ep.'curation_review_protocol_id')) {
                    $available = true;
                    $id = data_get($snapshot, $ep.'curation_review_protocol_id');
                    $prefix = $type === 'scvcep' ? 'SC-VCEP' : 'VCEP';
                    $value = match ((int) $id) {
                        1 => "Process #1: Biocurator review followed by $prefix discussion",
                        2 => "Process #2: Paired biocurator/expert review followed by expedited $prefix approval",
                        default => $id === null ? null : 'Protocol '.$id,
                    };
                }
            }
            $sections[] = ['key' => $path, 'label' => $label, 'available' => $available, 'value' => $value];
        }
        if ($step === 1) {
            foreach (['Genes' => 'relations.expertPanel.relations.genes', 'Members' => 'relations.members'] as $label => $path) {
                $values = data_get($snapshot, $path);
                $sections[] = ['key' => strtolower($label), 'label' => $label, 'available' => is_array($values), 'value' => is_array($values)
                    ? array_map(function ($item) use ($label, $type) {
                        if ($label === 'Genes') {
                            $gene = Arr::only($item['attributes'] ?? [], ['gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name', 'moi', 'tier', 'plan']);
                            if (isset($gene['tier']) && in_array((string) $gene['tier'], ['1', '2'], true)) {
                                $gene['tier'] = $type === 'gcep' ? ((int) $gene['tier'] === 1 ? 'Primary' : 'Secondary') : ((int) $gene['tier'] === 1 ? 'Current' : 'Future');
                            }
                            return $gene;
                        }
                        $person = Arr::only(data_get($item, 'relations.person.attributes', []), ['first_name', 'last_name', 'email']);
                        $person['roles'] = isset($item['relations']['roles']) ? array_map(fn ($role) => data_get($role, 'attributes.display_name', data_get($role, 'attributes.name')), $item['relations']['roles']) : 'Unavailable';
                        $person['retirement'] = array_key_exists('end_date', $item['attributes'] ?? []) ? ($item['attributes']['end_date'] === null ? 'Active' : 'Retired') : 'Unavailable';
                        foreach (['expertise' => 'expertises', 'credentials' => 'credentials'] as $field => $relation) {
                            $captured = data_get($item, 'relations.person.relations.'.$relation);
                            $person[$field] = is_array($captured) ? array_map(fn ($e) => data_get($e, 'attributes.name'), $captured) : 'Unavailable';
                        }
                        $institutionPath = 'relations.person.relations.institution';
                        $person['institution'] = Arr::has($item, $institutionPath)
                            ? data_get($item, $institutionPath.'.attributes.name') : 'Unavailable';
                        return $person;
                    }, $values) : null];
            }
        } elseif ($type !== 'gcep') {
            foreach (['Evidence Summaries' => 'review_state.evidence_summaries',
                'Core Approval Members' => 'review_state.core_approval_members',
                'Biocurator Trainers' => 'review_state.biocurator_trainers',
                'Trained Biocurators' => 'review_state.trained_biocurators'] as $label => $path) {
                $sections[] = ['key' => $path, 'label' => $label, 'available' => Arr::has($snapshot, $path), 'value' => data_get($snapshot, $path)];
            }
        }
        if (!$type) $sections[] = ['key' => 'group_type', 'label' => 'Application type', 'available' => false, 'value' => null];
        return $sections;
    }

    public function comparison(?array $before, ?array $after, int $step): array
    {
        $comparison = app(ScopeOfWorkDisplayComparison::class)->handle($before, $after);
        $beforeSections = collect($before ? $this->sections($before, $step) : [])->keyBy('key');
        $afterSections = collect($after ? $this->sections($after, $step) : [])->keyBy('key');
        // Keep the established gene/member field comparison; other fields use the same captured projection as Round 1.
        $comparison['changes'] = array_values(array_filter($comparison['changes'], fn ($c) => $step === 1 && in_array($c['section'], ['genes', 'members', 'member_roles'])));
        $comparison['unavailable_sections'] = $step === 1 ? array_values(array_filter($comparison['unavailable_sections'], fn ($key) => in_array($key, ['genes', 'members']) || str_starts_with($key, 'member_roles:'))) : [];
        if ($step === 4) $comparison['rows'] = ['genes' => null, 'members' => null];
        foreach ($afterSections as $key => $section) {
            if (in_array($key, ['genes', 'members'])) continue;
            $old = $beforeSections->get($key);
            if (!$old || !$old['available'] || !$section['available']) {
                $comparison['unavailable_sections'][] = $section['label'];
            } elseif ($old['value'] !== $section['value']) {
                $comparison['changes'][] = ['section' => 'application.'.$key, 'key' => $key, 'label' => $section['label'],
                    'operation' => 'modified', 'before' => $old['value'], 'after' => $section['value']];
            }
        }
        $comparison['status'] = $comparison['unavailable_sections'] ? 'partial' : 'complete';
        $comparison['summary']['changed_items'] = count($comparison['changes']);
        return $comparison;
    }
}
