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
            'cdwg_id' => 'nullable|exists:groups,id',
            'long_base_name' => [
                                    'nullable',
                                    'max:255',
                                ],
            'short_base_name' => [
                                    'nullable',
                                    'max:15',
                                ],
            'affiliation_id' => 'nullable|max:8',
        ];
    }

    public function prepareForValidation()
    {
        $longBaseName = !empty($this->long_base_name)
                            ? preg_replace("/ [GV]CEP$/", '', $this->long_base_name)
                            : null;
        $shortBaseName = !empty($this->short_base_name)
                            ? preg_replace("/ [GV]CEP$/", '', $this->short_base_name)
                            : null;
        $this->merge([
            'long_base_name' => $longBaseName,
            'short_base_name' => $shortBaseName
        ]);
    }
}
