<?php

namespace App\Enums;

enum ReportReason: string
{
    case NoShow      = 'no_show';
    case LateArrival = 'late_arrival';
    case QualityIssue = 'quality_issue';
    case Payment     = 'payment_issue';
    case Behaviour   = 'inappropriate_behaviour';
    case Other       = 'other';
}
