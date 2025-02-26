<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUpdateInfoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date_received' => 'required|date',
        ];
    }
}
