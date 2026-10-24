<?php

namespace App\Http\Requests;

class UpdateLeavePositionRequest extends StoreLeavePositionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['employee_id'], $rules['leave_type_id']);

        return [
            ...$rules,
            'employee_id' => ['prohibited'],
            'leave_type_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'employee_id.prohibited' => 'The employee cannot be changed.',
            'leave_type_id.prohibited' => 'The leave type cannot be changed.',
        ];
    }
}
