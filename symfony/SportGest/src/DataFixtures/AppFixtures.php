<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Utilisateur;
use App\Entity\Sportif;
use App\Entity\Coach;
use App\Entity\Seance;
use App\Entity\Exercice;
use App\Entity\FicheDePaie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Responsable;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    private function getRandomFirstName(): string
    {
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas', 'Julie', 'Nicolas', 'Emilie', 
                      'Lucas', 'Léa', 'Maxime', 'Laura', 'Alexandre', 'Camille', 'Antoine', 'Chloé'];
        return $firstNames[array_rand($firstNames)];
    }
    
    private function getRandomLastName(): string
    {
        $lastNames = ['Dupont', 'Martin', 'Durand', 'Lefebvre', 'Moreau', 'Simon', 'Laurent',
                     'Michel', 'Leroy', 'Garcia', 'Bernard', 'Thomas', 'Robert', 'Richard', 'Petit'];
        return $lastNames[array_rand($lastNames)];
    }
    
    private function getRandomEmail(string $firstName, string $lastName): string
    {
        $domains = ['gmail.com', 'yahoo.fr', 'hotmail.com', 'outlook.fr', 'free.fr', 'orange.fr'];
        return strtolower($firstName . '.' . $lastName . '@' . $domains[array_rand($domains)]);
    }
    
    private function getRandomFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
    
    private function getRandomDateInRange(string $start, string $end): \DateTimeImmutable
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $randomTimestamp = mt_rand($startDate->getTimestamp(), $endDate->getTimestamp());
        return \DateTimeImmutable::createFromMutable((new \DateTime())->setTimestamp($randomTimestamp));
    }

    public function load(ObjectManager $manager): void
    {
        // Création des exercices
        $exercices = $this->createExercices($manager, 15);
        
        // Création des coachs
        $coachs = $this->createCoachs($manager, 6);
        
        // Création des sportifs
        $sportifs = $this->createSportifs($manager, 20);
        
        // Création des séances
        $this->createSeances($manager, $coachs, $sportifs, $exercices, 30);
        
        // Création des fiches de paie
        $this->createFichesDePaie($manager, $coachs);

        $this->createResponsables($manager, 3);
        
        $manager->flush();
    }
    
    private function createExercices(ObjectManager $manager, int $count): array
    {
        $typesMuscles = ['pectoraux', 'biceps', 'triceps', 'abdominaux', 'dorsaux', 'épaules', 'jambes', 'quadriceps', 'fessiers', 'mollets', 'trapèzes'];
        $prefixesExercices = ['Renforcement des', 'Extension des', 'Flexion des', 'Développé', 'Soulevé de', 'Musculation des', 'Travail des'];
        $difficultes = ['facile', 'moyen', 'difficile'];
        $actions = ['renforcer', 'développer', 'tonifier', 'muscler', 'améliorer'];
        $nomsExercices = ['Pompes', 'Squats', 'Burpees', 'Abdominaux', 'Planche', 
                         'Mountain climbers', 'Fentes avant', 'Dips', 'Jumping jacks', 'Tractions',
                         'Gainage', 'Crunch', 'Tirage vertical', 'Développé couché', 'Rowing'];
        
        $exercices = [];
        for ($i = 0; $i < $count; $i++) {
            $exercice = new Exercice();
            
            // Générer un nom d'exercice aléatoire
            if (rand(0, 1) == 0) {
                // Nom spécifique
                $exercice->setNom($nomsExercices[array_rand($nomsExercices)]);
            } else {
                // Nom généré
                $prefixe = $prefixesExercices[array_rand($prefixesExercices)];
                $muscle = $typesMuscles[array_rand($typesMuscles)];
                $exercice->setNom($prefixe . ' ' . $muscle);
            }
            
            // Description aléatoire
            $exercice->setDescription('Exercice pour ' . $actions[array_rand($actions)] . ' ' . $typesMuscles[array_rand($typesMuscles)]);
            
            // Durée entre 5 et 30 minutes
            $exercice->setDureeEstimee(rand(5, 30));
            
            // Difficulté aléatoire
            $exercice->setDifficulte($difficultes[array_rand($difficultes)]);
            
            $manager->persist($exercice);
            $exercices[] = $exercice;
        }
        
        return $exercices;
    }
    
    private function createCoachs(ObjectManager $manager, int $count): array
    {
        $specialites = ['Fitness', 'Cardio', 'Musculation', 'Crossfit', 'Yoga', 'Pilates', 'Boxe', 'Natation'];
        
        $coachs = [];
        for ($i = 0; $i < $count; $i++) {
            $coach = new Coach();
            $nom = $this->getRandomLastName();
            $prenom = $this->getRandomFirstName();
            $coach->setNom($nom);
            $coach->setPrenom($prenom);
            $coach->setEmail($this->getRandomEmail($prenom, $nom));
            $coach->setPassword($this->passwordHasher->hashPassword($coach, 'password'));
            
            // Génére 1 à 4 spécialités aléatoires sans duplication
            $specialitesCount = rand(1, 4);
            $coachSpecialites = [];
            $specialitesShuffled = $specialites;
            shuffle($specialitesShuffled);
            
            for ($j = 0; $j < $specialitesCount; $j++) {
                $coachSpecialites[] = $specialitesShuffled[$j];
            }
            
            $coach->setSpecialites($coachSpecialites);
            
            // Tarif horaire entre 30 et 70€
            $coach->setTarifHoraire($this->getRandomFloat(30, 70));
            
            $manager->persist($coach);
            $coachs[] = $coach;
        }
        
        return $coachs;
    }
    
    private function createSportifs(ObjectManager $manager, int $count): array
    {
        $niveaux = ['Débutant', 'Intermédiaire', 'Avancé'];
        
        $sportifs = [];
        for ($i = 0; $i < $count; $i++) {
            $sportif = new Sportif();
            $nom = $this->getRandomLastName();
            $prenom = $this->getRandomFirstName();
            $sportif->setNom($nom);
            $sportif->setPrenom($prenom);
            $sportif->setEmail($this->getRandomEmail($prenom, $nom));
            $sportif->setPassword($this->passwordHasher->hashPassword($sportif, 'password'));
            
            // Date d'inscription aléatoire dans les 2 dernières années
            $sportif->setDateInscription($this->getRandomDateInRange('-2 years', 'now'));
            
            // Niveau sportif aléatoire
            $sportif->setNiveauSportif($niveaux[array_rand($niveaux)]);
            
            $manager->persist($sportif);
            $sportifs[] = $sportif;
        }
        
        return $sportifs;
    }

    private function createResponsables(ObjectManager $manager, int $count): array
    {
        $responsables = [];
        for ($i = 0; $i < $count; $i++) {
            $responsable = new Responsable();
            $nom = $this->getRandomLastName();
            $prenom = $this->getRandomFirstName();
            $responsable->setNom($nom);
            $responsable->setPrenom($prenom);
            $responsable->setEmail($this->getRandomEmail($prenom, $nom));
            $responsable->setPassword($this->passwordHasher->hashPassword($responsable, 'password'));
            $manager->persist($responsable);
            $responsables[] = $responsable;
        }
        return $responsables;
    }
    
    private function createSeances(ObjectManager $manager, array $coachs, array $sportifs, array $exercices, int $count): void
    {
        $themesSeance = ['Fitness', 'Cardio', 'Musculation', 'Crossfit', 'Yoga', 'Pilates', 'Boxe'];
        $typesSeance = ['Solo', 'Duo', 'Trio'];
        $niveauxSeance = ['Débutant', 'Intermédiaire', 'Avancé'];
        $statuts = ['Prévue', 'Validée', 'Annulée'];
        
        // Création des séances
        for ($i = 0; $i < $count; $i++) {
            $seance = new Seance();
            
            // Date entre -30 jours et +60 jours
            $jours = rand(-30, 60);
            $heures = rand(8, 20);
            $minutes = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
            $dateHeure = new \DateTime();
            $dateHeure->modify(($jours < 0 ? '-' : '+') . abs($jours) . ' days');
            $dateHeure->setTime($heures, $minutes);
            $seance->setDateHeure($dateHeure);
            
            // Type de séance aléatoire
            $typeSeance = $typesSeance[array_rand($typesSeance)];
            $seance->setTypeSeance($typeSeance);
            
            // Thème de séance aléatoire
            $themeSeance = $themesSeance[array_rand($themesSeance)];
            $seance->setThemeSeance($themeSeance);
            
            // Coach aléatoire
            $coach = $coachs[array_rand($coachs)];
            $seance->setCoach($coach);
            
            // Niveau de la séance aléatoire
            $seance->setNiveauSeance($niveauxSeance[array_rand($niveauxSeance)]);
            
            // Statut basé sur la date
            if ($jours < 0) {
                // Séance passée: validée ou annulée
                $seance->setStatut($statuts[array_rand([$statuts[1], $statuts[2]])]);
            } else {
                // Séance future: prévue
                $seance->setStatut($statuts[0]);
            }
            
            // Ajoute des sportifs en fonction du type de séance
            $maxSportifs = match($typeSeance) {
                'Solo' => 1,
                'Duo' => 2,
                'Trio' => 3,
                default => 1,
            };
            
            // Sélection aléatoire de sportifs de niveau correspondant
            $sportifsFiltres = array_filter($sportifs, function($sportif) use ($seance) {
                return $sportif->getNiveauSportif() === $seance->getNiveauSeance();
            });
            
            if (empty($sportifsFiltres)) {
                $sportifsFiltres = $sportifs;
            }
            
            // Mélange les sportifs et en prendre un nombre aléatoire jusqu'au maximum
            shuffle($sportifsFiltres);
            $nbSportifs = min(count($sportifsFiltres), $maxSportifs, rand(1, $maxSportifs));
            
            for ($j = 0; $j < $nbSportifs; $j++) {
                $seance->addSportif($sportifsFiltres[$j]);
            }
            
            // Ajouter des exercices (2 à 7 exercices par séance)
            $nbExercices = rand(2, 7);
            shuffle($exercices);
            
            for ($j = 0; $j < $nbExercices; $j++) {
                $seance->addExercice($exercices[$j]);
            }
            
            $manager->persist($seance);
        }
    }
    
    private function createFichesDePaie(ObjectManager $manager, array $coachs): void
    {
        // Générer des fiches de paie pour les 6 derniers mois
        $moisFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $anneeActuelle = (int)date('Y');
        
        // Obtiens les 6 derniers mois
        $moisActuel = (int)date('n') - 1; // 0-11
        $derniersMois = [];
        
        for ($i = 0; $i < 6; $i++) {
            $indexMois = ($moisActuel - $i + 12) % 12; // Pour gérer le passage à l'année précédente
            $annee = ($moisActuel - $i < 0) ? $anneeActuelle - 1 : $anneeActuelle;
            $derniersMois[] = ['mois' => $moisFr[$indexMois], 'annee' => $annee];
        }
        
        foreach ($coachs as $coach) {
            foreach ($derniersMois as $periode) {
                $fiche = new FicheDePaie();
                $fiche->setCoach($coach);
                $fiche->setPeriode($periode['mois'] . ' ' . $periode['annee']);
                
                // Nombre d'heures travaillées (entre 10 et 120)
                $heures = rand(10, 120);
                
                // Calcule le montant en fonction du tarif horaire du coach
                $tarifHoraire = $coach->getTarifHoraire();
                $montant = $heures * $tarifHoraire;
                
                // Utilise uniquement la méthode disponible
                $fiche->setMontantTotal($montant);

                $manager->persist($fiche);
            }
        }
    }
}