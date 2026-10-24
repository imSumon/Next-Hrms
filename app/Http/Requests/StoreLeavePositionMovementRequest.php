<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeavePositionMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('direction')) {
            $this->merge(['direction' => trim((string) $this->input('direction'))]);
        }
        if ($this->exists('related_reference')) {
            $value = trim((string) $this->input('related_reference'));
            $this->merge(['related_reference' => $value === '' ? null : $value]);
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
            'leave_position_id' => ['prohibited'],
            'amount' => ['required', 'numeric'],
            'direction' => ['required', 'string', 'regex:/\S/u'],
            'related_reference' => ['nullable', 'string', 'regex:/\S/u'],
            'reverses_movement_id' => ['nullable', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
            'kind' => ['prohibited'],
            'leave_kind' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'direction.required' => 'Direction is a recorded statement and is required.',
            'kind.prohibited' => 'A movement kind list is not authorized.',
            'leave_kind.prohibited' => 'A movement kind list is not authorized.',
        ];
    }
}
