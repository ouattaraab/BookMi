<?php

namespace App\Http\Requests\Api;

use App\Enums\MessageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
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
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'type'    => ['sometimes', 'string', Rule::enum(MessageType::class)],
        ];
    }
}
