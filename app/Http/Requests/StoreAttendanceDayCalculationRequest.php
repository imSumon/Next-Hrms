<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceDayCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['prohibited'],
            'employee_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'work_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
