<?php

namespace App\Enums;

enum PackageType: string
{
    case Essentiel = 'essentiel';
    case Standard = 'standard';
    case Premium = 'premium';
    case Micro = 'micro';
}
