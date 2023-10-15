<?php

namespace App\Modules\Person\Http\Requests;

use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $person = Person::findByUuidOrFail($this->route('uuid'));

        return [
            'email' => [
                'required',
                Rule::unique('people')->ignore($person->id),
            ],
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'nullable',
        ];
    }
}
