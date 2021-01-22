<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateApplicationRequest extends FormRequest
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
            'working_name' => 'required|max:256',
            'date_initiated' => 'nullable|date',
            'cdwg_id' => 'required|exists:cdwgs,id',
            'ep_type_id' => 'required|exists:ep_types,id',
        ];
    }
}
