<?php

namespace App\Http\Requests\Api;

use App\Models\Category;
use App\Models\TalentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTalentProfileRequest extends FormRequest
{
    private const ALLOWED_SOCIAL_KEYS = ['instagram', 'youtube', 'tiktok', 'facebook', 'twitter'];

    public function authorize(): bool
    {
        /** @var TalentProfile $profile */
        $profile = $this->route('talent_profile');

        return $this->user()?->can('update', $profile) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var TalentProfile $profile */
        $profile = $this->route('talent_profile');

        $categoryId = $this->input('category_id', $profile->category_id);

        return [
            'stage_name' => ['sometimes', 'string', 'max:100', Rule::unique('talent_profiles', 'stage_name')->ignore($profile->id)],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($categoryId): void {
                    if ($value && $categoryId) {
                        $isChild = Category::where('id', $value)
                            ->where('parent_id', $categoryId)
                            ->exists();
                        if (! $isChild) {
                            $fail('La sous-catégorie doit appartenir à la catégorie sélectionnée.');
                        }
                    }
                },
            ],
            'city' => ['sometimes', 'string', 'max:100'],
            'cachet_amount' => ['sometimes', 'integer', 'min:1000'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url'],
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
            'stage_name.max' => 'Le nom de scène ne doit pas dépasser 100 caractères.',
            'stage_name.unique' => 'Ce nom de scène est déjà utilisé.',
            'category_id.exists' => 'La catégorie sélectionnée est invalide.',
            'subcategory_id.exists' => 'La sous-catégorie sélectionnée est invalide.',
            'city.max' => 'La ville ne doit pas dépasser 100 caractères.',
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
