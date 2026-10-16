<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
        if ($this->exists('status')) {
            $this->merge(['status' => trim((string) $this->input('status'))]);
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
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'regex:/\S/u'],
            'status' => ['required', 'string', 'in:Active,withdrawn'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'id.prohibited' => 'The record identity cannot be changed.',
            'code.prohibited' => 'The code cannot be changed.',
            'name.required' => 'Name is required.',
            'status.required' => 'Status must be Active or withdrawn.',
            'status.in' => 'Status must be Active or withdrawn.',
        ];
    }
}
