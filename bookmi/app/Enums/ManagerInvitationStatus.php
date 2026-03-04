<?php

namespace App\Enums;

enum ManagerInvitationStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
