<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class PersonUpdateRequest extends FormRequest
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
        return [
            'email' => [
                'required',
                Rule::unique('people')->ignore($person->id)
            ],
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'nullable'
        ];
    }
}
