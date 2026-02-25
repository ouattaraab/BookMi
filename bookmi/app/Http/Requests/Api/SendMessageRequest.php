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
        $isMedia = in_array($this->input('type'), ['image', 'video'], true)
                || $this->hasFile('file');

        return [
            // Content is required for text messages, optional for media (can be a caption)
            'content' => $isMedia
                ? ['sometimes', 'nullable', 'string', 'max:1000']
                : ['required', 'string', 'min:1', 'max:5000'],
            'type'    => ['sometimes', 'string', Rule::enum(MessageType::class)],
            'file'    => ['sometimes', 'nullable', 'file', 'mimes:jpeg,jpg,png,gif,mp4,mov,webm', 'max:51200'], // 50 MB
        ];
    }
}
