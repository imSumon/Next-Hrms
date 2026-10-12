<?php

namespace App\Http\Requests;

use App\Employees\EmploymentEpisodeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'employee_id' => ['prohibited'],
            'joining_date' => ['required', 'date'],
            'episode_status' => ['required', Rule::in(EmploymentEpisodeRegistry::STATUSES)],
            'last_working_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the employee record, not from the form.',
            'employee_id.prohibited' => 'The episode stays on the employee in the URL.',
            'joining_date.required' => 'Joining date is required.',
            'episode_status.required' => 'Episode status is required.',
            'episode_status.in' => 'Episode status must be Open, inactive, or separated.',
        ];
    }

    /**
     * @return array{joining_date: string, episode_status: string, last_working_date: ?string}
     */
    public function episodeAttributes(): array
    {
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
