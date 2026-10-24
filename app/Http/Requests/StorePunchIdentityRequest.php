<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePunchIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['identity_value', 'effective_from', 'effective_to', 'reference'] as $field) {
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
            'employee_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'identity_value' => ['required', 'string', 'max:255', 'regex:/\S/u'],
            'effective_from' => ['required', 'string'],
            'effective_to' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
        ];
    }
}
