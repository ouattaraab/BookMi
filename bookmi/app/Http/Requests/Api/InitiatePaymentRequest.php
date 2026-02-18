<?php

namespace App\Http\Requests\Api;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
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
        $mobileMoneyMethods = [
            PaymentMethod::OrangeMoney->value,
            PaymentMethod::Wave->value,
            PaymentMethod::MtnMomo->value,
            PaymentMethod::MoovMoney->value,
        ];

        return [
            'booking_id'     => ['required', 'integer', 'exists:booking_requests,id'],
            'payment_method' => ['required', 'string', Rule::in([
                ...$mobileMoneyMethods,
                PaymentMethod::Card->value,
                PaymentMethod::BankTransfer->value,
            ])],
            // phone_number required only for Mobile Money methods
            'phone_number'   => [
                Rule::requiredIf(fn () => in_array($this->input('payment_method'), $mobileMoneyMethods)),
                'nullable',
                'string',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
        ];
    }
}
