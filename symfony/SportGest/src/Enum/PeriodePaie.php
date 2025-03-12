<?php

namespace App\Enum;

enum PeriodePaie: string
{
    case MOIS = 'mois';
    case SEMAINE = 'semaine';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 