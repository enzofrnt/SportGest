<?php

namespace App\Enum;

enum DifficulteExercice: string
{
    case FACILE = 'Facile';
    case MOYEN = 'Moyen';
    case DIFFICILE = 'Difficile';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 