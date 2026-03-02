<?php

namespace App\Http\Requests\Api;

use App\Enums\PackageType;
use App\Models\ServicePackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $talentProfileId = (int) $this->input('talent_profile_id');
        if (! $talentProfileId || ! $this->user()) {
            return true; // La validation règles gérera le cas manquant
        }

        // Empêcher un talent de réserver son propre profil
        return ! \App\Models\TalentProfile::where('id', $talentProfileId)
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isMicro = $this->isMicroPackage();

        return [
            'talent_profile_id'  => [
                'required',
                'integer',
                Rule::exists('talent_profiles', 'id')->where(
                    fn ($q) => $q->where('is_verified', true)
                ),
            ],
            'service_package_id' => [
                'required',
                'integer',
                Rule::exists('service_packages', 'id')->where(
                    fn ($q) => $q->where('talent_profile_id', $this->input('talent_profile_id'))
                                 ->whereNull('deleted_at')
                ),
            ],
            // Micro-service: event_date is optional — auto-set from delivery_days in BookingService
            'event_date'     => $isMicro
                ? ['nullable', 'date', 'date_format:Y-m-d']
                : ['required', 'date', 'date_format:Y-m-d', 'after:today'],
            'start_time'     => ['nullable', 'date_format:H:i'],
            // Micro-service: location defaults to "Livraison digitale"
            'event_location' => $isMicro
                ? ['nullable', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'message'        => ['nullable', 'string', 'max:1000'],
            'travel_cost'    => ['nullable', 'integer', 'min:0'],
            'is_express'     => ['nullable', 'boolean'],
            'promo_code'     => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Returns true when the requested service_package is of type Micro.
     */
    private function isMicroPackage(): bool
    {
        $packageId = $this->input('service_package_id');
        if (! $packageId) {
            return false;
        }
        $pkg = ServicePackage::find($packageId);

        return $pkg && ($pkg->type instanceof PackageType
            ? $pkg->type === PackageType::Micro
            : $pkg->type === PackageType::Micro->value);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'talent_profile_id.required'  => 'Le profil talent est obligatoire.',
            'talent_profile_id.integer'   => 'L\'identifiant du talent est invalide.',
            'talent_profile_id.exists'    => 'Le talent est introuvable ou n\'est pas encore vérifié.',
            'service_package_id.required' => 'Le package de service est obligatoire.',
            'service_package_id.integer'  => 'L\'identifiant du package est invalide.',
            'service_package_id.exists'   => 'Le package sélectionné n\'appartient pas à ce talent.',
            'event_date.required'         => 'La date de l\'événement est obligatoire.',
            'event_date.date'             => 'La date de l\'événement est invalide.',
            'event_date.date_format'      => 'La date doit être au format AAAA-MM-JJ.',
            'event_date.after'            => 'La date de l\'événement doit être strictement dans le futur.',
            'event_location.required'     => 'Le lieu de l\'événement est obligatoire.',
            'event_location.max'          => 'Le lieu ne peut pas dépasser 255 caractères.',
            'message.max'                 => 'Le message ne peut pas dépasser 1000 caractères.',
        ];
    }
}
