<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateApplicationRequest extends FormRequest
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
            'uuid' => 'required|uuid',
            'working_name' => 'required|max:256|min:3',
            'date_initiated' => 'nullable|date',
            'cdwg_id' => 'nullable|exists:groups,id',
            'expert_panel_type_id' => 'required|exists:expert_panel_types,id',
        ];
    }

    public function messages()
    {
        return [
            'cdwg_id.exists' => 'The selected cdwg is invalid.',
            'expert_panel_type_id.exists' => 'The selected expert panel type is invalid.',
        ];
    }
}
