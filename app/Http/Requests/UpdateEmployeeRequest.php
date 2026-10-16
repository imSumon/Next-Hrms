<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('display_name')) {
            $this->merge([
                'display_name' => trim((string) $this->input('display_name')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['prohibited'],
            'employee_code' => ['prohibited'],
            'id' => ['prohibited'],
            'episodes' => ['prohibited'],
            'display_name' => ['required', 'string', 'regex:/\S/u'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'employee_code.prohibited' => 'The employee code cannot be changed. Separation does not release a code for reuse.',
            'id.prohibited' => 'The employee identity cannot be changed.',
            'episodes.prohibited' => 'Employment episodes are not changed from this profile.',
            'display_name.required' => 'Display name is required.',
        ];
    }
}
