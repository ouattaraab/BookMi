<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by 'admin' middleware on the route
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'format'     => ['sometimes', 'in:csv'],
        ];
    }
}
