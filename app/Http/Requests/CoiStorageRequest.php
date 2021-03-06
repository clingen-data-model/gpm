<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class CoiStorageRequest extends FormRequest
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
        $coiDefinition = json_decode(file_get_contents(base_path('resources/surveys/coi.json')));

        if (request()->has('document_uuid')) {
            $coiDefinition = json_decode(file_get_contents(base_path('resources/surveys/legacy_coi.json')));
        }


        $rules = [];
        foreach ($coiDefinition->questions as $question) {
            if (isset($question->validation)) {
                $rules[$question->name] = $question->validation;
            }
        }
        if (request()->missing('document_uuid')) {
            $rules['group_member_id'] = 'required|exists:group_members,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'required' => 'This field is required.',
            'required_if' => 'This field is required.',
            'group_member_id.required' => 'Storing a COI requires a group member id',
        ];
    }
}
