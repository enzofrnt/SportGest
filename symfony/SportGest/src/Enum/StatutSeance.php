<?php

namespace App\Enum;

enum StatutSeance: string
{
    case PREVUE = 'Prévue';
    case VALIDEE = 'Validée';
    case ANNULEE = 'Annulée';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 