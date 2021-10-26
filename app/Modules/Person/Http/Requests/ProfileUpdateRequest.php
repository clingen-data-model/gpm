<?php

namespace App\Modules\Person\Http\Requests;

use DateTimeZone;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
        $person = Person::findByUuidOrFail($this->route('uuid'));
        $rules = [
            'institution_id' => ['exists:institutions,id'],
            'race_id' => ['exists:races,id'],
            'primary_occupation_id' => ['exists:primary_occupations,id'],
            'gender_id' => ['exists:genders,id'],
            'country_id' => ['exists:countries,id'],
            'timezone' => [Rule::in(DateTimeZone::listIdentifiers())],
        ];

        if ($person->user_id == Auth::user()->id) {
            foreach ($rules as $field => $rule) {
                $rules[$field][] = 'required';
            }
            return $rules;
        }

        foreach ($rules as $field => $rule) {
            $rules[$field][] = 'nullable';
        }
        return $rules;
    }
}