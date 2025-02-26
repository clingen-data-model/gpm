<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateApplicationRequest extends FormRequest
{
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
}
