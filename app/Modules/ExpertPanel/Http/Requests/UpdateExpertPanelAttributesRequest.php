<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpertPanelAttributesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($this->route('app_uuid'));
        // dd($expertPanel);
        return [
            'cdwg_id' => 'nullable|exists:groups,id',
            'long_base_name' => [
                                    'nullable',
                                    'max:255',
                                    Rule::unique('expert_panels', 'long_base_name')
                                        ->ignore($expertPanel->id)
                                    ->where(function ($query) use ($expertPanel) {
                                        $query->whereNotNull('long_base_name')
                                            ->where('expert_panel_type_id', $expertPanel->expert_panel_type_id);
                                    })
                                ],
            'short_base_name' => [
                                    'nullable',
                                    'max:15',
                                    Rule::unique('expert_panels', 'short_base_name')
                                        ->ignore($expertPanel->id)
                                    ->where(function ($query) use ($expertPanel) {
                                        $query->whereNotNull('short_base_name')
                                            ->where('expert_panel_type_id', $expertPanel->expert_panel_type_id);
                                    })
                                ],
            'affiliation_id' => 'nullable|digits:5',
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
