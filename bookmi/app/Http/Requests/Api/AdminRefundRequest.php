<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by 'admin' middleware on the route
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
