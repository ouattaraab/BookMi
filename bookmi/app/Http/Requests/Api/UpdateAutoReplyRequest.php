<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutoReplyRequest extends FormRequest
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
            'auto_reply_message'   => ['required', 'string', 'min:1', 'max:2000'],
            'auto_reply_is_active' => ['required', 'boolean'],
        ];
    }
}
