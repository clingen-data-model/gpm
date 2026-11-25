<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InactivationAnswersValidate
{
    public function handle(Group $group, array $answers, bool $submit = false, array $previous = []): array
    {
        $contacts = app(InactivationContacts::class);
        $answers = $contacts->normalize($answers);
        $previous = $contacts->normalize($previous);
        $required = $submit ? 'required' : 'nullable';
        $transfer = ($answers['transfer_planned'] ?? null) === 'yes';
        $transferRequired = $submit && $transfer ? 'required' : 'nullable';
        if (is_array($answers['liaisons'] ?? null)) $answers['liaisons'] = $contacts->synchronize($group, $answers['liaisons']);
        if (is_array($answers['continuing_members'] ?? null)) $answers['continuing_members'] = $contacts->synchronize($group, $answers['continuing_members']);
        $receiverId = $answers['receiving_group_id'] ?? null;
        $receiver = is_scalar($receiverId) && $receiverId ? Group::find($receiverId) : null;
        if ($transfer && $receiver) {
            $answers['receiving_name'] = $receiver->display_name;
            $answers['receiving_affiliation_id'] = (string) $receiver->affiliation_id;
            $answers['receiving_parent_name'] = $receiver->parent?->display_name;
            if (is_array($answers['receiving_liaisons'] ?? null)) $answers['receiving_liaisons'] = $contacts->synchronize($receiver, $answers['receiving_liaisons']);
        }
        $rules = [
            'group_name' => [$required, 'string', 'max:255'],
            'affiliation_id' => [$submit && !$group->is_gcep ? 'required' : 'nullable', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'request_date' => [$required, 'date_format:Y-m-d'],
            'liaisons' => [$submit ? 'required' : 'present', 'array', 'max:30'],
            'transfer_planned' => [$required, Rule::in($group->is_gcep ? ['yes'] : ['yes', 'no'])],
            'receiving_group_id' => [$transferRequired, 'integer', ...($transfer ? [Rule::exists('groups', 'id')->where(fn ($q) => $q
                ->where('id', '!=', $group->id)
                ->whereNull('deleted_at')
                ->where('group_status_id', config('groups.statuses.active.id'))
                ->whereIn('group_type_id', $group->is_gcep ? [config('groups.types.gcep.id')] : [config('groups.types.vcep.id'), config('groups.types.scvcep.id')]))] : [])],
            'receiving_name' => [$transferRequired, 'string', 'max:255'],
            'receiving_affiliation_id' => [$submit && $transfer && !$group->is_gcep ? 'required' : 'nullable', 'string', 'max:255'],
            'receiving_parent_name' => ['nullable', 'string', 'max:255'],
            'receiving_liaisons' => [$transferRequired, 'array', 'max:30'],
            'transfer_discussion_date' => [$submit && $transfer && !$group->is_gcep ? 'required' : 'nullable', 'date_format:Y-m-d'],
            'transfer_attestation' => $submit && $transfer ? ['accepted'] : ['nullable', 'boolean'],
            'no_transfer_attestation' => $submit && !$group->is_gcep && !$transfer ? ['accepted'] : ['nullable', 'boolean'],
            'public_text' => [$required, 'string', 'max:15000'],
            'materials_access' => ['nullable', 'string', 'max:15000'],
            'publications' => ['present', 'array', 'max:300'],
            'publications.*' => ['array:publication_id,pmid,pmcid,title,pmc_compliant'],
            'publications.*.publication_id' => ['nullable', 'string', 'max:255'],
            'publications.*.pmid' => ['nullable', 'string', 'max:255'],
            'publications.*.pmcid' => ['nullable', 'string', 'max:255'],
            'publications.*.title' => ['nullable', 'string', 'max:2000'],
            'publications.*.pmc_compliant' => ['required', Rule::in(['yes', 'no', 'unknown'])],
            'publications_acknowledged' => $submit ? ['accepted'] : ['nullable', 'boolean'],
            'legacy_publications' => ['nullable', 'array'],
            'continuing_members' => ['present', 'array', 'max:300'],
            'continuing_members.*' => ['array:member_id,person_id,name,role,first_name,last_name,email'],
            'continuing_members.*.member_id' => ['nullable', 'integer', Rule::exists('group_members', 'id')->where('group_id', $group->id)],
            'continuing_members.*.first_name' => ['nullable', 'string', 'max:255'],
            'continuing_members.*.last_name' => ['nullable', 'string', 'max:255'],
            'continuing_members.*.name' => [$required, 'string', 'max:255'],
            'continuing_members.*.person_id' => ['nullable', 'integer'],
            'continuing_members.*.role' => ['nullable', 'string', 'max:1000'],
            'continuing_members.*.email' => [$required, 'email', 'max:255'],
        ];
        foreach (['liaisons' => $group->id, 'receiving_liaisons' => $receiver?->id ?? 0] as $field => $groupId) {
            $contactRequired = $field === 'liaisons' ? $required : $transferRequired;
            $rules["$field.*"] = ['array:member_id,person_id,name,email,role'];
            $rules["$field.*.member_id"] = ['nullable', 'integer'];
            if ($field === 'liaisons' || $transfer) {
                $rules["$field.*.member_id"][] = Rule::exists('group_members', 'id')->where('group_id', $groupId)->whereNull('deleted_at');
            }
            $rules["$field.*.person_id"] = ['nullable', 'integer'];
            $rules["$field.*.name"] = [$contactRequired, 'string', 'max:255'];
            $rules["$field.*.email"] = [$contactRequired, 'email', 'max:255'];
            $rules["$field.*.role"] = ['nullable', 'string', 'max:1000'];
        }
        $validator = Validator::make($answers, $rules);
        $validator->after(function ($validator) use ($answers, $submit, $previous, $transfer) {
            foreach (['liaisons', 'receiving_liaisons', 'continuing_members'] as $field) {
                $seen = [];
                foreach (is_array($answers[$field] ?? null) ? $answers[$field] : [] as $index => $contact) {
                    if (!is_array($contact)) continue;
                    if ($field !== 'continuing_members' && empty($contact['member_id'])) {
                        $legacy = collect($previous[$field] ?? [])->contains(fn ($old) => is_array($old) && empty($old['member_id'])
                            && ($old['name'] ?? '') === ($contact['name'] ?? '') && ($old['email'] ?? '') === ($contact['email'] ?? ''));
                        if (!$legacy || ($submit && ($field === 'liaisons' || $transfer))) {
                            $validator->errors()->add("$field.$index.member_id", 'Select a liaison from the GPM member list. Existing manual contacts may be kept in drafts but must be replaced before submission.');
                        }
                    }
                    foreach (['member_id', 'email'] as $key) {
                        $value = $contact[$key] ?? null;
                        if (!is_scalar($value) || !$value) continue;
                        $identity = $key.':'.strtolower(trim((string) $value));
                        if (isset($seen[$identity])) $validator->errors()->add("$field.$index.$key", 'This contact has already been selected.');
                        $seen[$identity] = true;
                    }
                }
            }
            if (!$submit) return;
            if (is_string($answers['public_text'] ?? null) && !InactivationText::hasText(InactivationText::clean($answers['public_text']))) $validator->errors()->add('public_text', 'Enter public-facing explanatory text.');
        });
        // Preserve hidden transfer answers when No Transfer is selected.
        $data = $validator->validate();
        foreach (['public_text', 'materials_access'] as $field) {
            if (is_string($data[$field] ?? null)) $data[$field] = InactivationText::clean($data[$field]);
        }
        return $data;
    }
}
