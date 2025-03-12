<?php

namespace App\Enum;

enum NiveauSportif: string
{
    case DEBUTANT = 'débutant';
    case INTERMEDIAIRE = 'intermédiaire';
    case AVANCE = 'avancé';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 