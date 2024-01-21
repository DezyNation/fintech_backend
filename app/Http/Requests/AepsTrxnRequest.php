<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AepsTrxnRequest extends FormRequest
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
        $allowed_services = ['MS', 'CW', 'BE'];
        return [
            'aadhaar' => ['required', 'digits:12'],
            'amount' => ['required', 'integer', 'min:1'],
            'serviceType' => ['required', Rule::in($allowed_services)],
            'piddata' => ['required', 'string'],
            'authenticity' => ['required'],
            'bankCode' => ['required'],
            'latitude' => ['required', 'between:-90,90'],
            'longitude' => ['required', 'between:-180,180'],
        ];
    }
}
