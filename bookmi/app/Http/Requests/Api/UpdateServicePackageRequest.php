<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateServicePackageRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'cachet_amount' => ['sometimes', 'integer', 'min:1000', 'max:2000000000'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'inclusions' => ['sometimes', 'nullable', 'array'],
            'inclusions.*' => ['string', 'max:200'],
            'type' => ['sometimes', 'string', Rule::in(['essentiel', 'standard', 'premium', 'micro'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $newType = $this->input('type');
            /** @var \App\Models\ServicePackage|null $package */
            $package = $this->route('service_package');

            if ($newType !== null && $newType !== 'micro') {
                $newDuration = $this->input('duration_minutes');
                $existingDuration = $package?->duration_minutes;

                if ($newDuration === null && $existingDuration === null) {
                    $validator->errors()->add(
                        'duration_minutes',
                        'La durée est obligatoire pour ce type de package.',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Le nom du package ne doit pas dépasser 150 caractères.',
            'cachet_amount.integer' => 'Le montant du cachet doit être un nombre entier (en centimes).',
            'cachet_amount.min' => 'Le montant du cachet doit être au minimum 1000 centimes (10 FCFA).',
            'cachet_amount.max' => 'Le montant du cachet dépasse la limite autorisée.',
            'duration_minutes.integer' => 'La durée doit être un nombre entier en minutes.',
            'duration_minutes.min' => 'La durée doit être au minimum 1 minute.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
            'type.in' => 'Le type de package doit être : essentiel, standard, premium ou micro.',
            'inclusions.array' => 'Les inclusions doivent être un tableau.',
            'inclusions.*.string' => 'Chaque inclusion doit être une chaîne de caractères.',
            'inclusions.*.max' => 'Chaque inclusion ne doit pas dépasser 200 caractères.',
            'sort_order.integer' => "L'ordre de tri doit être un nombre entier.",
            'sort_order.min' => "L'ordre de tri doit être positif.",
        ];
    }
}
