<?php

namespace App\Enums;

enum ReviewType: string
{
    case ClientToTalent = 'client_to_talent';
    case TalentToClient = 'talent_to_client';
}
