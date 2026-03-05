<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // [H3] Removed `exists:users,phone` — would reveal whether a number is registered.
            // AuthService::resendOtp() silently no-ops for unknown numbers.
            'phone' => ['required', 'string', 'regex:/^\+225[0-9]{10}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex'    => 'Le numéro de téléphone doit être au format +225 suivi de 10 chiffres.',
        ];
    }
}
