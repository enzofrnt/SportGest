<?php

namespace App\Enum;

enum TypeSeance: string
{
    case SOLO = 'Solo';
    case DUO = 'Duo';
    case TRIO = 'Trio';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 