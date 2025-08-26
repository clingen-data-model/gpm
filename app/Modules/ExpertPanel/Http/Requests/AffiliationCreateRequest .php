<?php
namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AffiliationCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void {
        if ($this->has('type'))   $this->merge(['type'=>strtoupper($this->input('type'))]);
        if ($this->has('status')) $this->merge(['status'=>strtoupper($this->input('status'))]);
    }
    public function rules(): array
    {
        return [
            'uuid'       => ['required','string','max:255'],
            'full_name'  => ['required','string','max:255'],
            'short_name' => ['required','string','max:255'],
            'clinical_domain_working_group' => ['nullable','integer'],
            'type'   => ['required','in:VCEP,GCEP,SC_VCEP,INDEPENDENT_CURATION'],
            'status' => ['required','in:APPLYING,ACTIVE,INACTIVE,RETIRED,ARCHIVED'],
            'members' => ['nullable','string','max:4000'],
            'coordinators' => ['nullable','array'],
            'coordinators.*.coordinator_name'  => ['required_with:coordinators','string','max:255'],
            'coordinators.*.coordinator_email' => ['required_with:coordinators','email','max:255'],
            'approvers' => ['nullable','array'],
            'approvers.*.approver_name' => ['required_with:approvers','string','max:255'],
            'clinvar_submitter_ids' => ['nullable','array'],
            'clinvar_submitter_ids.*.clinvar_submitter_id' => ['required_with:clinvar_submitter_ids'],
        ];
    }
}
