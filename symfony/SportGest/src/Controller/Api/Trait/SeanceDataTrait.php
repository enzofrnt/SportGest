<?php

namespace App\Controller\Api\Trait;

use App\Entity\Seance;

trait SeanceDataTrait
{
    private function getSeanceData(Seance $seance): array
    {
        return [
            'id' => $seance->getId(),
            'theme' => [
                'id' => $seance->getTheme()->getId(),
                'nom' => $seance->getTheme()->getNom()
            ],
            'dateHeure' => $seance->getDateHeure()->format('Y-m-d H:i:s'),
            'typeSeance' => $seance->getTypeSeance()->value,
            'statut' => $seance->getStatut()->value,
            'niveauSeance' => $seance->getNiveauSeance()->value,
            'coach' => [
                'id' => $seance->getCoach()->getId(),
                'nom' => $seance->getCoach()->getNom(),
                'prenom' => $seance->getCoach()->getPrenom(),
            ],
            'dureeSeance'=> $seance->getDureeSeance(),
            'nbSportifs' => $seance->getReservations()->count(),
            'exercices' => array_map(function($exercice) {
                return [
                    'id' => $exercice->getId(),
                    'nom' => $exercice->getNom()
                ];
            }, $seance->getExercices()->toArray())
        ];
    }
} 