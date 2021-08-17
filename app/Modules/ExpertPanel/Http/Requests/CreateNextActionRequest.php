<?php

namespace App\Modules\ExpertPanel\Http\Requests;

use App\Modules\ExpertPanel\Models\NextActionAssignee;
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
            'assigned_to' => 'required|exists:next_action_assignees,id'
        ];
    }

    public function messages()
    {
        $assignees = NextActionAssignee::getAll();
        return [
            'assigned_to.exists' => 'The next action must be assigned to '.$assignees->pluck('name')->join(', ', ', or ')
        ];
    }
}
