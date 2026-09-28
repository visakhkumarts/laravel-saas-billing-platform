<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],

            'effective_at' => [
                'required',
                'date',
            ],
        ];
    }
}
