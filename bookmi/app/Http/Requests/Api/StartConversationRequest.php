<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
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
            'talent_profile_id'  => ['required', 'integer', 'exists:talent_profiles,id'],
            'booking_request_id' => ['sometimes', 'nullable', 'integer', 'exists:booking_requests,id'],
            'message'            => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
