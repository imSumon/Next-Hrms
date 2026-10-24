<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeavePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['source_evidence', 'stated_basis', 'status', 'period_label'] as $field) {
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
            'leave_type_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'proposed_amount' => ['required', 'numeric'],
            'period_label' => ['nullable', 'string', 'regex:/\S/u'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'source_evidence' => ['required', 'string', 'regex:/\S/u'],
            'stated_basis' => ['required', 'string', 'regex:/\S/u'],
            'status' => ['required', 'string', 'in:proposed,excepted'],
            'confirmation_recorded_at' => ['prohibited'],
            'confirmation_actor_id' => ['prohibited'],
            'confirmation_decision' => ['prohibited'],
            'confirmed' => ['prohibited'],
            'usable_as_approved_balance' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'status.in' => 'Status must be proposed or excepted.',
            'confirmation_recorded_at.prohibited' => 'Confirmation cannot be recorded.',
            'confirmation_actor_id.prohibited' => 'Confirmation cannot be recorded.',
            'confirmation_decision.prohibited' => 'Confirmation cannot be recorded.',
            'confirmed.prohibited' => 'Confirmation cannot be recorded.',
        ];
    }
}
