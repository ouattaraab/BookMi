<?php

namespace App\Http\Requests\Api;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreTalentProfileRequest extends FormRequest
{
    private const ALLOWED_SOCIAL_KEYS = ['instagram', 'youtube', 'tiktok', 'facebook', 'twitter'];

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
            'stage_name'      => ['required', 'string', 'max:100', 'unique:talent_profiles,stage_name'],
            'category_ids'    => ['required', 'array', 'min:1'],
            'category_ids.*'  => ['integer', 'exists:categories,id'],
            // Legacy single-category support (backward compat for old clients)
            'category_id'     => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id'  => ['nullable', 'integer', 'exists:categories,id'],
            'city'            => ['required', 'string', 'max:100'],
            'cachet_amount'   => ['required', 'integer', 'min:1000'],
            'bio'             => ['nullable', 'string', 'max:1000'],
            'social_links'    => ['nullable', 'array'],
            'social_links.*'  => ['nullable', 'url'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key === null && isset($validated['social_links']) && is_array($validated['social_links'])) {
            $validated['social_links'] = array_intersect_key(
                $validated['social_links'],
                array_flip(self::ALLOWED_SOCIAL_KEYS),
            );
        }

        return $validated;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage_name.required' => 'Le nom de scène est obligatoire.',
            'stage_name.max' => 'Le nom de scène ne doit pas dépasser 100 caractères.',
            'stage_name.unique' => 'Ce nom de scène est déjà utilisé.',
            'category_ids.required' => 'Au moins une catégorie est obligatoire.',
            'category_ids.min' => 'Au moins une catégorie est obligatoire.',
            'category_ids.*.exists' => 'Une des catégories sélectionnées est invalide.',
            'subcategory_id.exists' => 'La sous-catégorie sélectionnée est invalide.',
            'city.required' => 'La ville est obligatoire.',
            'city.max' => 'La ville ne doit pas dépasser 100 caractères.',
            'cachet_amount.required' => 'Le montant du cachet est obligatoire.',
            'cachet_amount.integer' => 'Le montant du cachet doit être un nombre entier (en centimes).',
            'cachet_amount.min' => 'Le montant du cachet doit être au minimum 1000 centimes (10 FCFA).',
            'bio.max' => 'La bio ne doit pas dépasser 1000 caractères.',
            'social_links.array' => 'Les liens sociaux doivent être un objet JSON.',
            'social_links.instagram.url' => 'Le lien Instagram doit être une URL valide.',
            'social_links.youtube.url' => 'Le lien YouTube doit être une URL valide.',
            'social_links.tiktok.url' => 'Le lien TikTok doit être une URL valide.',
            'social_links.facebook.url' => 'Le lien Facebook doit être une URL valide.',
            'social_links.twitter.url' => 'Le lien Twitter doit être une URL valide.',
        ];
    }
}
