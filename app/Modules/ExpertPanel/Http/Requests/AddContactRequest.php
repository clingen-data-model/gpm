<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddContactRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'person_uuid' => 'required|uuid|exists:people,uuid',
        ];
    }

    public function messages()
    {
        return [
            'person_uuid.exists' => 'The person must already exist in the database.'
        ];
    }
}
