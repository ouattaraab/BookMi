<?php

namespace App\Enums;

enum DisputeReason: string
{
    case NoShow             = 'no_show';
    case LateArrival        = 'late_arrival';
    case PoorQuality        = 'poor_quality';
    case DifferentFromDesc  = 'different_from_description';
    case EarlyTermination   = 'early_termination';
    case CommunicationIssue = 'communication_issue';
    case Other              = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NoShow             => 'Talent ne s\'est pas présenté',
            self::LateArrival        => 'Retard important du talent',
            self::PoorQuality        => 'Prestation de mauvaise qualité',
            self::DifferentFromDesc  => 'Prestation différente de la description',
            self::EarlyTermination   => 'Prestation interrompue prématurément',
            self::CommunicationIssue => 'Problème de communication',
            self::Other              => 'Autre problème',
        };
    }
}
