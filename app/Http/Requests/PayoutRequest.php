<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayoutRequest extends FormRequest
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
            'provider' => ['required', Rule::exists('services', 'provider')->where('active', true)],
            'account_number' => ['required', 'digits_between:9,17'],
            'ifsc_code' => ['required', 'string', 'regex:/^[A-Za-z]{4}\d{7}$/'],
            'beneficiary_name' => ['required', 'string'],
            'mode' => ['required'],
            'remarks' => ['nullable'],
            'amount' => ['required', 'numeric', 'min:1']
        ];
    }
}
