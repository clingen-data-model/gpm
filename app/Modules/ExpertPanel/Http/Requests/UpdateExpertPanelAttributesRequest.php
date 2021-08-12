<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Validation\Rule;
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
        $expertPanel = ExpertPanel::findByUuidOrFail($this->route('app_uuid'));
        return [
            'working_name' => 'required|max:255',
            'cdwg_id' => 'nullable|exists:cdwgs,id',
            'long_base_name' => [
                                    'nullable',
                                    'max:255',
                                    Rule::unique('applications')
                                        ->ignore($expertPanel)
                                        ->where(function ($query) use ($expertPanel) {
                                            return $query->where('ep_type_id', $expertPanel->ep_type_id);
                                        })
                                ],
            'short_base_name' => [
                                    'nullable',
                                    'max:15',
                                    Rule::unique('applications')
                                        ->ignore($expertPanel)
                                        ->where(function ($query) use ($expertPanel) {
                                            $query->where('ep_type_id', $expertPanel->ep_type_id);
                                            return $query;
                                        })
                                ],
            'affiliation_id' => 'nullable|max:8',
        ];
    }

    public function messages()
    {
        return [
            'long_base_name.unique' => 'The long base name must be unique for expert panels of this type.',
            'short_base_name.unique' => 'The short base name must be unique for expert panels of this type.',
        ];
    }
    

    public function prepareForValidation()
    {
        $this->merge([
            'long_base_name' => preg_replace("/ [GV]CEP$/", '', $this->long_base_name),
            'short_base_name' => preg_replace("/ [GV]CEP$/", '', $this->short_base_name)
        ]);
    }
}
