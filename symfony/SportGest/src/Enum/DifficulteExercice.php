<?php

namespace App\Enum;

enum DifficulteExercice: string
{
    case FACILE = 'facile';
    case MOYEN = 'moyen';
    case DIFFICILE = 'difficile';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 