<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchTalentRequest extends FormRequest
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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_cachet' => ['nullable', 'integer', 'min:0'],
            'max_cachet' => ['nullable', 'integer', 'min:0'],
            'city' => ['nullable', 'string', 'max:100'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'sort_by' => ['nullable', 'string', 'in:rating,cachet_amount,created_at,distance'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator) {
                if (
                    $this->filled('min_cachet')
                    && $this->filled('max_cachet')
                    && (int) $this->input('min_cachet') > (int) $this->input('max_cachet')
                ) {
                    $validator->errors()->add(
                        'min_cachet',
                        'Le montant minimum du cachet doit être inférieur ou égal au montant maximum.',
                    );
                }
            },
            function (\Illuminate\Validation\Validator $validator) {
                $hasLat = $this->filled('lat');
                $hasLng = $this->filled('lng');
                $hasRadius = $this->filled('radius');
                $anyGeo = $hasLat || $hasLng || $hasRadius;
                $allGeo = $hasLat && $hasLng && $hasRadius;

                if ($anyGeo && ! $allGeo) {
                    $validator->errors()->add(
                        'lat',
                        'Les paramètres lat, lng et radius doivent tous être fournis ensemble.',
                    );
                }
            },
            function (\Illuminate\Validation\Validator $validator) {
                if (
                    $this->input('sort_by') === 'distance'
                    && (! $this->filled('lat') || ! $this->filled('lng'))
                ) {
                    $validator->errors()->add(
                        'sort_by',
                        'Le tri par distance nécessite les paramètres lat et lng.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.integer' => 'L\'identifiant de catégorie doit être un nombre entier.',
            'category_id.exists' => 'La catégorie sélectionnée est invalide.',
            'subcategory_id.integer' => 'L\'identifiant de sous-catégorie doit être un nombre entier.',
            'subcategory_id.exists' => 'La sous-catégorie sélectionnée est invalide.',
            'min_cachet.integer' => 'Le montant minimum du cachet doit être un nombre entier (en centimes).',
            'min_cachet.min' => 'Le montant minimum du cachet ne peut pas être négatif.',
            'max_cachet.integer' => 'Le montant maximum du cachet doit être un nombre entier (en centimes).',
            'max_cachet.min' => 'Le montant maximum du cachet ne peut pas être négatif.',
            'city.max' => 'La ville ne doit pas dépasser 100 caractères.',
            'min_rating.numeric' => 'La note minimale doit être un nombre.',
            'min_rating.min' => 'La note minimale ne peut pas être négative.',
            'min_rating.max' => 'La note minimale ne peut pas dépasser 5.',
            'lat.numeric' => 'La latitude doit être un nombre.',
            'lat.between' => 'La latitude doit être comprise entre -90 et 90.',
            'lng.numeric' => 'La longitude doit être un nombre.',
            'lng.between' => 'La longitude doit être comprise entre -180 et 180.',
            'radius.numeric' => 'Le rayon doit être un nombre.',
            'radius.min' => 'Le rayon doit être d\'au moins 1 km.',
            'radius.max' => 'Le rayon ne peut pas dépasser 500 km.',
            'sort_by.in' => 'Le tri doit être par rating, cachet_amount, created_at ou distance.',
            'sort_direction.in' => 'La direction du tri doit être asc ou desc.',
            'per_page.integer' => 'Le nombre par page doit être un nombre entier.',
            'per_page.min' => 'Le nombre par page doit être au minimum 1.',
            'per_page.max' => 'Le nombre par page ne peut pas dépasser 50.',
        ];
    }
}
