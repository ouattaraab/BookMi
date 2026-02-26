<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.integer'  => 'Le montant doit être un entier.',
            'amount.min'      => 'Le montant minimum est de 1 000 XOF.',
        ];
    }
}
