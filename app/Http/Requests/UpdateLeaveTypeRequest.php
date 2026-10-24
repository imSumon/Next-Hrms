<?php

namespace App\Http\Requests;

class UpdateLeaveTypeRequest extends StoreLeaveTypeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'paid' => ['prohibited'],
            'unpaid' => ['prohibited'],
        ];
    }
}
