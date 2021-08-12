<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date_approved' => 'required|date',
            'notify_contacts' => 'nullable'
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'notify_contacts' => filter_var($this->notify_contacts, FILTER_VALIDATE_BOOL),
        ]);
    }
}
