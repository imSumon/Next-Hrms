<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPunchLogRequest extends FormRequest
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
            'source_name' => ['required', 'string', 'regex:/\S/u'],
            'mode' => ['required', 'in:preview,commit'],
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*' => ['array'],
            'rows.*.employee_code' => ['present', 'nullable', 'string'],
            'rows.*.identity_value' => ['present', 'nullable', 'string'],
            'rows.*.event_at' => ['present', 'nullable', 'string'],
            'rows.*.source_event_id' => ['nullable', 'string'],
        ];
    }
}
