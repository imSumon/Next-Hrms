<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDeviceLogBatchRequest extends FormRequest
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
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'confirm' => ['sometimes', 'boolean'],
            'fingerprint' => ['required_if:confirm,true', 'nullable', 'string', 'size:64'],
            'event_at' => ['prohibited'],
            'timezone' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.prohibited' => 'The company is taken from the signed-in company context, not from the form.',
            'event_at.prohibited' => 'The batch uses the source wall time as stored. A converted event time is not accepted.',
            'timezone.prohibited' => 'The source timestamp basis is not approved. A timezone is not accepted on the batch.',
            'fingerprint.required_if' => 'Confirm the preview fingerprint before any import attempt.',
        ];
    }
}
