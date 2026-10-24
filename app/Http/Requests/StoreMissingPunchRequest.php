<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissingPunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['reason', 'requested_event_at', 'punch_side'] as $field) {
            if ($this->exists($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['prohibited'],
            'request_state' => ['prohibited'],
            'employee_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'punch_side' => ['required', 'in:in,out'],
            'requested_event_at' => ['required', 'string'],
            'reason' => ['required', 'string', 'regex:/\S/u'],
        ];
    }
}
