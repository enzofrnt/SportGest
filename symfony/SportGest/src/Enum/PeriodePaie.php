<?php

namespace App\Enum;

enum PeriodePaie: string
{
    case MOIS = 'Mois';
    case SEMAINE = 'Semaine';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 