<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddContactRequest extends FormRequest
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
        return [
            'person_uuid' => 'required|uuid|exists:people,uuid',
        ];
    }

    public function messages()
    {
        return [
            'person_uuid.exists' => 'The person must already exist in the database.',
        ];
    }
}
