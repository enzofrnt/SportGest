<?php

namespace App\Enum;

enum NiveauSportif: string
{
    case DEBUTANT = 'Débutant';
    case INTERMEDIAIRE = 'Intermédiaire';
    case AVANCE = 'Avancé';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 