<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRescheduleRequestRequest extends FormRequest
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
            'proposed_date' => ['required', 'date', 'date_format:Y-m-d', 'after:today'],
            'message'       => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proposed_date.required'    => 'La date proposée est obligatoire.',
            'proposed_date.date'        => 'La date proposée est invalide.',
            'proposed_date.date_format' => 'La date doit être au format AAAA-MM-JJ.',
            'proposed_date.after'       => 'La date proposée doit être dans le futur.',
            'message.max'               => 'Le message ne peut pas dépasser 500 caractères.',
        ];
    }
}
