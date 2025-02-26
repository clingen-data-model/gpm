<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationDocumentStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'uuid' => 'required|uuid',
            'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file',
            'date_received' => 'nullable|date',
        ];
    }
}
