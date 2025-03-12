<?php

namespace App\Enum;

enum StatutSeance: string
{
    case PREVUE = 'prévue';
    case VALIDEE = 'validée';
    case ANNULEE = 'annulée';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 