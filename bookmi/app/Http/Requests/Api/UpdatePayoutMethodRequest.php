<?php

namespace App\Http\Requests\Api;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayoutMethodRequest extends FormRequest
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
            'payout_method'  => ['required', 'string', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'payout_details' => ['required', 'array'],
            'payout_details.phone' => [
                Rule::requiredIf(fn () => in_array($this->input('payout_method'), [
                    PaymentMethod::OrangeMoney->value,
                    PaymentMethod::Wave->value,
                    PaymentMethod::MtnMomo->value,
                    PaymentMethod::MoovMoney->value,
                ])),
                'nullable',
                'string',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
        ];
    }
}
