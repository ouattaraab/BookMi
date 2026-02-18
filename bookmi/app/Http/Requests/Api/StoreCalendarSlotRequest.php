<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarSlotRequest extends FormRequest
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
            'date'   => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'status' => ['required', Rule::in(['available', 'blocked', 'rest'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required'          => 'La date est obligatoire.',
            'date.date'              => 'La date fournie est invalide.',
            'date.date_format'       => 'La date doit être au format AAAA-MM-JJ.',
            'date.after_or_equal'    => 'La date ne peut pas être dans le passé.',
            'status.required'        => 'Le statut est obligatoire.',
            'status.Illuminate\Validation\Rules\Enum' => 'Le statut doit être : available, blocked ou rest.',
        ];
    }
}
