<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUpdateInfoRequest extends FormRequest
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
            'date_received' => 'required|date',
            'date_reviewed' => 'date'
        ];
    }
}
