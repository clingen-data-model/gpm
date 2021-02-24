<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationContactRequest extends FormRequest
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
        return [
            'uuid' => 'required|uuid',
            'first_name' => 'required|max:256',
            'last_name' => 'required|max:256',
            'email' => 'required|email|unique:people,email',
            'phone' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'This email address is already associated with a person in the system.'
        ];
    }
    
}
