<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideAttendanceCorrectionRequest extends FormRequest
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
            'decision' => ['required', 'string', 'in:approved,rejected'],
        ];
    }
}
