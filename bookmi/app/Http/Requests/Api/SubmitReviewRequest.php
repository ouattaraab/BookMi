<?php

namespace App\Http\Requests\Api;

use App\Enums\ReviewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitReviewRequest extends FormRequest
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
            'type'                   => ['required', 'string', Rule::enum(ReviewType::class)],
            'rating'                 => ['required', 'integer', 'between:1,5'],
            'punctuality_score'      => ['nullable', 'integer', 'between:1,5'],
            'quality_score'          => ['nullable', 'integer', 'between:1,5'],
            'professionalism_score'  => ['nullable', 'integer', 'between:1,5'],
            'contract_respect_score' => ['nullable', 'integer', 'between:1,5'],
            'comment'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
