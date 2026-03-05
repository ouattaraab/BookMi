<?php

namespace App\Http\Requests\Api;

use App\Enums\ConsentType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+225[0-9]{10}$/', 'unique:users,phone'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'role' => ['required', 'string', 'in:' . implode(',', UserRole::registrableRoles())],
            // category_ids (new multi-select) or legacy category_id — both validated but not stored.
            // At least one is required for talent role. The talent associates categories
            // when creating their TalentProfile (POST /talent_profiles).
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            // Required when role=talent AND category_ids is not provided (backward compat)
            'category_id' => [
                Rule::requiredIf(
                    fn () => $this->input('role') === 'talent'
                        && empty($this->input('category_ids')),
                ),
                'nullable', 'integer', 'exists:categories,id',
            ],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'referral_code' => ['nullable', 'string', 'max:20'],

            // Consentements obligatoires
            'consents'     => ['required', 'array'],
            'consents.' . ConsentType::CguPrivacy->value             => ['required', 'accepted'],
            'consents.' . ConsentType::DataProcessing->value         => ['required', 'accepted'],
            'consents.' . ConsentType::AgeMinimum->value             => ['required', 'accepted'],
            'consents.' . ConsentType::SurveillanceModeration->value => ['required', 'accepted'],
            'consents.' . ConsentType::PlatformCommunication->value  => ['required', 'accepted'],
            'consents.' . ConsentType::DisputeResolution->value      => ['required', 'accepted'],
            'consents.' . ConsentType::LiabilityDisclaimer->value    => ['required', 'accepted'],
            'consents.' . ConsentType::Indemnification->value        => ['required', 'accepted'],
            'consents.' . ConsentType::CollectiveWaiver->value       => ['required', 'accepted'],

            // Consentements rôle talent
            'consents.' . ConsentType::ProfilePublication->value    => [
                Rule::requiredIf(fn () => $this->input('role') === 'talent'),
                'boolean',
            ],
            'consents.' . ConsentType::CommissionEscrow->value      => [
                Rule::requiredIf(fn () => $this->input('role') === 'talent'),
                'boolean',
            ],
            'consents.' . ConsentType::FiscalObligations->value     => [
                Rule::requiredIf(fn () => $this->input('role') === 'talent'),
                'boolean',
            ],
            'consents.' . ConsentType::ReservationEngagement->value => [
                Rule::requiredIf(fn () => $this->input('role') === 'talent'),
                'boolean',
            ],

            // Consentements rôle client
            'consents.' . ConsentType::NonClientLiability->value  => [
                Rule::requiredIf(fn () => $this->input('role') === 'client'),
                'boolean',
            ],
            'consents.' . ConsentType::CancellationPolicy->value  => [
                Rule::requiredIf(fn () => $this->input('role') === 'client'),
                'boolean',
            ],

            // Consentements rôle manager
            'consents.' . ConsentType::ManagerMandate->value => [
                Rule::requiredIf(fn () => $this->input('role') === 'manager'),
                'boolean',
            ],

            // Opt-ins (facultatifs)
            'consents.' . ConsentType::PushNotifications->value   => ['nullable', 'boolean'],
            'consents.' . ConsentType::Marketing->value            => ['nullable', 'boolean'],
            'consents.' . ConsentType::Geolocation->value          => ['nullable', 'boolean'],
            'consents.' . ConsentType::ImageRights->value          => ['nullable', 'boolean'],
            'consents.' . ConsentType::SatisfactionSurveys->value  => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 100 caractères.',
            'last_name.required' => 'Le nom de famille est obligatoire.',
            'last_name.max' => 'Le nom de famille ne doit pas dépasser 100 caractères.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le numéro de téléphone doit être au format +225 suivi de 10 chiffres.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être client ou talent.',
            'category_id.required'    => 'La catégorie est obligatoire pour un talent.',
            'category_id.exists'      => 'La catégorie sélectionnée est invalide.',
            'category_ids.*.exists'   => 'Une des catégories sélectionnées est invalide.',
            'subcategory_id.exists'   => 'La sous-catégorie sélectionnée est invalide.',
            'consents.required'       => 'Les consentements sont obligatoires.',
            'consents.cgu_privacy.required'             => "L'acceptation des CGU est obligatoire.",
            'consents.cgu_privacy.accepted'             => "Vous devez accepter les CGU et la politique de confidentialité.",
            'consents.data_processing.required'         => 'Le consentement au traitement des données est obligatoire.',
            'consents.data_processing.accepted'         => 'Vous devez accepter le traitement de vos données personnelles.',
            'consents.age_minimum.required'             => "La confirmation d'âge est obligatoire.",
            'consents.age_minimum.accepted'             => 'Vous devez confirmer avoir au moins 18 ans.',
            'consents.surveillance_moderation.accepted' => 'Vous devez accepter les conditions de surveillance et modération.',
            'consents.platform_communication.accepted'  => 'Vous devez accepter les communications de la plateforme.',
            'consents.dispute_resolution.accepted'      => 'Vous devez accepter la clause de résolution des litiges.',
            'consents.liability_disclaimer.accepted'    => 'Vous devez accepter la limitation de responsabilité.',
            'consents.indemnification.accepted'         => "Vous devez accepter la clause d'indemnisation.",
            'consents.collective_waiver.accepted'       => 'Vous devez accepter la renonciation collective.',
        ];
    }
}
