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

        $rules = [];
        foreach ($coiDefinition->questions as $question) {
            if($question->validation) {
                $rules[$question->name] = $question->validation;
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'required' => 'This field is required.',
            'required_if' => 'This field is required.'
        ];
    }
    
}
