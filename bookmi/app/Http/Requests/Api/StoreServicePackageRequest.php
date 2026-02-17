<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServicePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cachet_amount' => ['required', 'integer', 'min:1000', 'max:2000000000'],
            'duration_minutes' => [
                Rule::requiredIf(fn () => $this->input('type') !== 'micro'),
                'nullable',
                'integer',
                'min:1',
            ],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'max:200'],
            'type' => ['required', 'string', Rule::in(['essentiel', 'standard', 'premium', 'micro'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du package est obligatoire.',
            'name.max' => 'Le nom du package ne doit pas dépasser 150 caractères.',
            'cachet_amount.required' => 'Le montant du cachet est obligatoire.',
            'cachet_amount.integer' => 'Le montant du cachet doit être un nombre entier (en centimes).',
            'cachet_amount.min' => 'Le montant du cachet doit être au minimum 1000 centimes (10 FCFA).',
            'cachet_amount.max' => 'Le montant du cachet dépasse la limite autorisée.',
            'duration_minutes.required' => 'La durée est obligatoire pour ce type de package.',
            'duration_minutes.integer' => 'La durée doit être un nombre entier en minutes.',
            'duration_minutes.min' => 'La durée doit être au minimum 1 minute.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
            'type.required' => 'Le type de package est obligatoire.',
            'type.in' => 'Le type de package doit être : essentiel, standard, premium ou micro.',
            'inclusions.array' => 'Les inclusions doivent être un tableau.',
            'inclusions.*.string' => 'Chaque inclusion doit être une chaîne de caractères.',
            'inclusions.*.max' => 'Chaque inclusion ne doit pas dépasser 200 caractères.',
            'sort_order.integer' => "L'ordre de tri doit être un nombre entier.",
            'sort_order.min' => "L'ordre de tri doit être positif.",
        ];
    }
}
