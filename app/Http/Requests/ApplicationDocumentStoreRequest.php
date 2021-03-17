<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationDocumentStoreRequest extends FormRequest
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
            'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file',
            'date_received' => 'nullable|date',
            'date_reviewed' => 'nullable|date',
        ];
    }
}
