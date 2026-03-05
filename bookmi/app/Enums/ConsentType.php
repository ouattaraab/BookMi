<?php

namespace App\Enums;

enum ConsentType: string
{
    // ── Obligatoires (tous rôles) ──────────────────────────────────
    case CguPrivacy            = 'cgu_privacy';
    case DataProcessing        = 'data_processing';
    case AgeMinimum            = 'age_minimum';

    // ── Règles plateforme (inscription) ──────────────────────────
    case SurveillanceModeration = 'surveillance_moderation';
    case PlatformCommunication  = 'platform_communication';
    case DisputeResolution      = 'dispute_resolution';
    case LiabilityDisclaimer    = 'liability_disclaimer';
    case Indemnification        = 'indemnification';
    case CollectiveWaiver       = 'collective_waiver';

    // ── Rôle Talent ───────────────────────────────────────────────
    case ProfilePublication     = 'profile_publication';
    case CommissionEscrow       = 'commission_escrow';
    case FiscalObligations      = 'fiscal_obligations';
    case ReservationEngagement  = 'reservation_engagement';

    // ── Rôle Client ───────────────────────────────────────────────
    case NonClientLiability     = 'non_client_liability';
    case CancellationPolicy     = 'cancellation_policy';

    // ── Rôle Manager ─────────────────────────────────────────────
    case ManagerMandate         = 'manager_mandate';

    // ── Opt-in ────────────────────────────────────────────────────
    case PushNotifications      = 'push_notifications';
    case Marketing              = 'marketing';
    case Geolocation            = 'geolocation';
    case ImageRights            = 'image_rights';
    case SatisfactionSurveys    = 'satisfaction_surveys';

    // ── Transactionnel ────────────────────────────────────────────
    case TransactionPayment      = 'transaction_payment';
    case TransactionCancellation = 'transaction_cancellation';

    // ── Re-consentement ───────────────────────────────────────────
    case CguUpdate              = 'cgu_update';

    /** Consentements obligatoires pour tous les rôles. */
    public static function required(): array
    {
        return [
            self::CguPrivacy,
            self::DataProcessing,
            self::AgeMinimum,
            self::SurveillanceModeration,
            self::PlatformCommunication,
            self::DisputeResolution,
            self::LiabilityDisclaimer,
            self::Indemnification,
            self::CollectiveWaiver,
        ];
    }

    /** Consentements obligatoires supplémentaires par rôle. */
    public static function requiredForRole(string $role): array
    {
        return match ($role) {
            'talent'  => [
                self::ProfilePublication,
                self::CommissionEscrow,
                self::FiscalObligations,
                self::ReservationEngagement,
            ],
            'client'  => [
                self::NonClientLiability,
                self::CancellationPolicy,
            ],
            'manager' => [
                self::ManagerMandate,
            ],
            default   => [],
        };
    }

    /** Consentements opt-in modifiables après inscription. */
    public static function optIn(): array
    {
        return [
            self::PushNotifications,
            self::Marketing,
            self::Geolocation,
            self::ImageRights,
            self::SatisfactionSurveys,
        ];
    }

    /** Consentements requis pour le re-consentement CGU. */
    public static function forReconsent(): array
    {
        return [
            self::CguUpdate,
            self::DataProcessing,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::CguPrivacy             => 'CGU et politique de confidentialité',
            self::DataProcessing         => 'Traitement des données personnelles',
            self::AgeMinimum             => 'Âge minimum (18 ans)',
            self::SurveillanceModeration => 'Surveillance et modération',
            self::PlatformCommunication  => 'Communications de la plateforme',
            self::DisputeResolution      => 'Résolution des litiges',
            self::LiabilityDisclaimer    => 'Limitation de responsabilité',
            self::Indemnification        => 'Indemnisation',
            self::CollectiveWaiver       => 'Renonciation collective',
            self::ProfilePublication     => 'Publication du profil talent',
            self::CommissionEscrow       => 'Commission et séquestre',
            self::FiscalObligations      => 'Obligations fiscales',
            self::ReservationEngagement  => 'Engagement de réservation',
            self::NonClientLiability     => 'Non-responsabilité client',
            self::CancellationPolicy     => 'Politique d\'annulation',
            self::ManagerMandate         => 'Mandat de gestion',
            self::PushNotifications      => 'Notifications push',
            self::Marketing              => 'Communications marketing',
            self::Geolocation            => 'Géolocalisation',
            self::ImageRights            => 'Droit à l\'image',
            self::SatisfactionSurveys    => 'Enquêtes de satisfaction',
            self::TransactionPayment     => 'Consentement au paiement',
            self::TransactionCancellation => 'Politique d\'annulation transactionnelle',
            self::CguUpdate              => 'Mise à jour des CGU',
        };
    }
}
