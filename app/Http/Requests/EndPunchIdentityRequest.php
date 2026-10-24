<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndPunchIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['effective_to', 'reason'] as $field) {
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
            'effective_to' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:500', 'regex:/\S/u'],
        ];
    }
}
