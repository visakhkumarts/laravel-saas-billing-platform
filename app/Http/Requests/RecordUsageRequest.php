<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'integer', 'exists:merchants,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'units' => ['required', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
        ];
    }
}
