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
        return false;
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
            'planId' => ['required', 'exists:plans,id'],
            'roleId' => ['required', 'exists:roles,id'],
            'fixedCharge' => ['required', 'numeric', 'min:0'],
            'isFlat' => ['required', 'boolean'],
            'commission' => ['required', 'numeric', 'min:0'],
        ];
    }
}
