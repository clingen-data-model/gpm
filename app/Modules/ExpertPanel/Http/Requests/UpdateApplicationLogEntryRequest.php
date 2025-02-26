<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationLogEntryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'entry' => 'required',
            'log_date' => 'required|date',
            'step' => 'nullable|numeric|between:1,4'
        ];
    }
}
