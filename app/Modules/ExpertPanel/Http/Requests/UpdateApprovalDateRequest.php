<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalDateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'step' => 'required|integer|min:1|max:4',
            'date_approved' => 'required|date'
        ];
    }
}
