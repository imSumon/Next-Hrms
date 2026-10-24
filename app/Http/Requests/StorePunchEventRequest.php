<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePunchEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['identity_value', 'source_name', 'source_event_id'] as $field) {
            if ($this->exists($field)) {
                $value = trim((string) $this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
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
            'id' => ['prohibited'],
            'employee_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'identity_value' => ['required', 'string', 'regex:/\S/u'],
            'source_name' => ['required', 'string', 'regex:/\S/u'],
            'event_at' => ['required', 'date'],
            'source_event_id' => ['nullable', 'string', 'regex:/\S/u'],
            'ingested_at' => ['prohibited'],
            'acting_user_id' => ['prohibited'],
            'area' => ['prohibited'],
            'balid' => ['prohibited'],
            'work_date' => ['prohibited'],
            'withdrawal_status' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'ingested_at.prohibited' => 'Ingestion time is recorded by the server.',
            'acting_user_id.prohibited' => 'The submitting actor is the signed-in user.',
            'area.prohibited' => 'A named source is required. Area is not a source.',
            'balid.prohibited' => 'A named source is required.',
            'work_date.prohibited' => 'A punch event stores an event time. It does not store a work date.',
            'withdrawal_status.prohibited' => 'A punch event cannot be withdrawn.',
            'status.prohibited' => 'A punch event does not store an attendance status.',
        ];
    }
}
