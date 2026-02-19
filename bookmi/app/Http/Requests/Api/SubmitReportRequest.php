<?php

namespace App\Http\Requests\Api;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitReportRequest extends FormRequest
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
            'reason'      => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
