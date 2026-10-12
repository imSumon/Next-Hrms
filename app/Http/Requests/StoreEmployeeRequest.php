<?php

namespace App\Http\Requests;

use App\Employees\EmploymentEpisodeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_code' => trim((string) $this->input('employee_code', '')),
            'display_name' => trim((string) $this->input('display_name', '')),
            'joining_date' => $this->blankToNull('joining_date'),
            'episode_status' => $this->blankToNull('episode_status'),
            'last_working_date' => $this->blankToNull('last_working_date'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['prohibited'],
            'employee_code' => ['required', 'string', 'regex:/\S/u'],
            'display_name' => ['required', 'string', 'regex:/\S/u'],
            'joining_date' => ['nullable', 'date', 'required_with:episode_status,last_working_date'],
            'episode_status' => ['nullable', 'required_with:joining_date,last_working_date', Rule::in(EmploymentEpisodeRegistry::STATUSES)],
            'last_working_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'employee_code.required' => 'Employee code is required.',
            'display_name.required' => 'Display name is required.',
            'episode_status.in' => 'Episode status must be Open, inactive, or separated.',
        ];
    }

    /**
     * @return array{employee_code: string, display_name: string}
     */
    public function employeeAttributes(): array
    {
        return [
            'employee_code' => $this->string('employee_code')->toString(),
            'display_name' => $this->string('display_name')->toString(),
        ];
    }

    /**
     * @return array{joining_date: string, episode_status: string, last_working_date: ?string}|null
     */
    public function episodeAttributes(): ?array
    {
        if ($this->input('joining_date') === null && $this->input('episode_status') === null && $this->input('last_working_date') === null) {
            return null;
        }

        return [
            'joining_date' => $this->string('joining_date')->toString(),
            'episode_status' => $this->string('episode_status')->toString(),
            'last_working_date' => $this->input('last_working_date'),
        ];
    }

    private function blankToNull(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
