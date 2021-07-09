<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;

class CreateNextActionRequest extends FormRequest
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
            'entry' => 'required',
            'date_created' => 'required|date',
            'target_date' => 'nullable|date',
            'date_completed' => 'nullable|date',
            'step' => 'nullable|int|between:1,4',
            'assigned_to' => 'required|in:CDWG OC,Expert Panel'
        ];
    }

    public function messages()
    {
        return [
            'assigned_to.in' => 'The next action must be assigned to the "CDWG OC" or "Expert Panel"'
        ];
    }
}
