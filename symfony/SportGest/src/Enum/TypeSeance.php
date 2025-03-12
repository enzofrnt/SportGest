<?php

namespace App\Enum;

enum TypeSeance: string
{
    case SOLO = 'solo';
    case DUO = 'duo';
    case TRIO = 'trio';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 