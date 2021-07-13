<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpertPanelAttributesRequest extends FormRequest
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
            'working_name' => 'required|max:255',
            'cdwg_id' => 'nullable|exists:cdwgs,id',
            'long_base_name' => 'nullable|max:255',
            'short_base_name' => 'nullable|max:15',
            'affiliation_id' => 'nullable|max:8',
        ];
    }

    /**
     * @test
     */
    public function prepareForValidation()
    {
        $this->merge([
            'loong_base_name' => preg_replace("/ [GV]CEP$/", '', $this->long_base_name),
            'short_base_name' => preg_replace("/ [GV]CEP$/", '', $this->short_base_name)
        ]);
    }
}
