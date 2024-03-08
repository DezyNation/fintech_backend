<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'min:1', 'numeric'],
            'to' => ['required', 'min:1', 'numeric'],
            'service' => ['nullable', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'fixed_charge' => ['required', 'numeric', 'min:0'],
            'is_flat' => ['required', 'boolean'],
            'commission' => ['required', 'numeric', 'min:0'],
        ];
    }
}
