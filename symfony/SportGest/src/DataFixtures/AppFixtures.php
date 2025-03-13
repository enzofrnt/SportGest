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
use App\Entity\Responsable;
use App\Enum\DifficulteExercice;
use App\Enum\NiveauSportif;
use App\Enum\TypeSeance;
use App\Enum\StatutSeance;
use App\Enum\PeriodePaie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    private array $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas', 'Julie', 'Nicolas', 'Emilie', 
                               'Lucas', 'Léa', 'Maxime', 'Laura', 'Alexandre', 'Camille', 'Antoine', 'Chloé'];
    
    private array $lastNames = ['Dupont', 'Martin', 'Durand', 'Lefebvre', 'Moreau', 'Simon', 'Laurent',
                              'Michel', 'Leroy', 'Garcia', 'Bernard', 'Thomas', 'Robert', 'Richard', 'Petit'];
    
    private array $domains = ['gmail.com', 'yahoo.fr', 'hotmail.com', 'outlook.fr', 'free.fr', 'orange.fr'];

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    private function getRandomFirstName(): string
    {
        return $this->firstNames[array_rand($this->firstNames)];
    }
    
    private function getRandomLastName(): string
    {
        return $this->lastNames[array_rand($this->lastNames)];
    }
    
    private function getRandomEmail(string $firstName, string $lastName): string
    {
        return strtolower($firstName . '.' . $lastName . '@' . $this->domains[array_rand($this->domains)]);
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

        $this->createAdmin($manager);
        
        $manager->flush();
    }

    private function createExercices(ObjectManager $manager, int $count): array
    {
        $typesMuscles = ['pectoraux', 'biceps', 'triceps', 'abdominaux', 'dorsaux', 'épaules', 'jambes', 'quadriceps', 'fessiers', 'mollets', 'trapèzes'];
        $prefixesExercices = ['Renforcement des', 'Extension des', 'Flexion des', 'Développé', 'Soulevé de', 'Musculation des', 'Travail des'];
        $difficultes = DifficulteExercice::cases();
        $actions = ['renforcer', 'développer', 'tonifier', 'muscler', 'améliorer'];
        $nomsExercices = ['Pompes', 'Squats', 'Burpees', 'Abdominaux', 'Planche', 
                         'Mountain climbers', 'Fentes avant', 'Dips', 'Jumping jacks', 'Tractions',
                         'Gainage', 'Crunch', 'Tirage vertical', 'Développé couché', 'Rowing'];
        
        $exercices = [];
        for ($i = 0; $i < $count; $i++) {
            $exercice = new Exercice();
            
            if (rand(0, 1) == 0) {
                $exercice->setNom($nomsExercices[array_rand($nomsExercices)]);
            } else {
                $prefixe = $prefixesExercices[array_rand($prefixesExercices)];
                $muscle = $typesMuscles[array_rand($typesMuscles)];
                $exercice->setNom($prefixe . ' ' . $muscle);
            }
            
            $exercice->setDescription('Exercice pour ' . $actions[array_rand($actions)] . ' ' . $typesMuscles[array_rand($typesMuscles)]);
            $exercice->setDureeEstimee(rand(5, 30));
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
            
            $specialitesCount = rand(1, 4);
            $coachSpecialites = [];
            $specialitesShuffled = $specialites;
            shuffle($specialitesShuffled);
            
            for ($j = 0; $j < $specialitesCount; $j++) {
                $coachSpecialites[] = $specialitesShuffled[$j];
            }
            
            $coach->setSpecialites($coachSpecialites);
            $coach->setTarifHoraire($this->getRandomFloat(30, 70));
            
            $manager->persist($coach);
            $coachs[] = $coach;
        }

        // Coach de test
        $coach = new Coach();
        $coach->setNom('Dupont');
        $coach->setPrenom('Jean');
        $coach->setEmail('coach@sportgest.fr');
        $coach->setPassword($this->passwordHasher->hashPassword($coach, 'password'));
        $coach->setSpecialites(['Fitness', 'Musculation']);
        $coach->setTarifHoraire(50.0);
        $manager->persist($coach);

        $coachs[] = $coach;
        
        return $coachs;
    }

    private function createSportifs(ObjectManager $manager, int $count): array
    {
        $niveaux = NiveauSportif::cases();
        
        $sportifs = [];
        for ($i = 0; $i < $count; $i++) {
            $sportif = new Sportif();
            $nom = $this->getRandomLastName();
            $prenom = $this->getRandomFirstName();
            $sportif->setNom($nom);
            $sportif->setPrenom($prenom);
            $sportif->setEmail($this->getRandomEmail($prenom, $nom));
            $sportif->setPassword($this->passwordHasher->hashPassword($sportif, 'password'));
            $sportif->setDateInscription($this->getRandomDateInRange('-2 years', 'now'));
            $sportif->setNiveauSportif($niveaux[array_rand($niveaux)]);
            
            $manager->persist($sportif);
            $sportifs[] = $sportif;
        }

        // Sportif de test
        $sportif = new Sportif();
        $sportif->setNom('Dubois');
        $sportif->setPrenom('Pierre');
        $sportif->setEmail('sportif@sportgest.fr');
        $sportif->setPassword($this->passwordHasher->hashPassword($sportif, 'password'));
        $sportif->setDateInscription(new \DateTimeImmutable('1990-01-01'));
        $sportif->setNiveauSportif(NiveauSportif::INTERMEDIAIRE);
        $manager->persist($sportif);

        $sportifs[] = $sportif;

        return $sportifs;
    }
    
    private function createSeances(ObjectManager $manager, array $coachs, array $sportifs, array $exercices, int $count): void
    {
        $themesSeance = ['Fitness', 'Cardio', 'Musculation', 'Crossfit', 'Yoga', 'Pilates', 'Boxe'];
        $typesSeance = TypeSeance::cases();
        $niveauxSeance = NiveauSportif::cases();
        $statuts = StatutSeance::cases();
        
        for ($i = 0; $i < $count; $i++) {
            $seance = new Seance();
            
            $jours = rand(-30, 60);
            $heures = rand(8, 20);
            $minutes = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
            $dateHeure = new \DateTime();
            $dateHeure->modify(($jours < 0 ? '-' : '+') . abs($jours) . ' days');
            $dateHeure->setTime($heures, $minutes);
            $seance->setDateHeure($dateHeure);
            
            $typeSeance = $typesSeance[array_rand($typesSeance)];
            $seance->setTypeSeance($typeSeance);
            $seance->setThemeSeance($themesSeance[array_rand($themesSeance)]);
            $seance->setCoach($coachs[array_rand($coachs)]);
            $seance->setNiveauSeance($niveauxSeance[array_rand($niveauxSeance)]);
            
            if ($jours < 0) {
                $seance->setStatut($statuts[array_rand([1, 2])]);
            } else {
                $seance->setStatut(StatutSeance::PREVUE);
            }
            
            $maxSportifs = match($typeSeance) {
                TypeSeance::SOLO => 1,
                TypeSeance::DUO => 2,
                TypeSeance::TRIO => 3,
            };
            
            $sportifsFiltres = array_filter($sportifs, function($sportif) use ($seance) {
                return $sportif->getNiveauSportif() === $seance->getNiveauSeance();
            });
            
            if (empty($sportifsFiltres)) {
                $sportifsFiltres = $sportifs;
            }
            
            shuffle($sportifsFiltres);
            $nbSportifs = min(count($sportifsFiltres), $maxSportifs, rand(1, $maxSportifs));
            
            for ($j = 0; $j < $nbSportifs; $j++) {
                $seance->addSportif($sportifsFiltres[$j]);
            }
            
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
        foreach ($coachs as $coach) {
            // Fiches mensuelles
            for ($i = 0; $i < 6; $i++) {
                $fiche = new FicheDePaie();
                $fiche->setCoach($coach);
                $fiche->setPeriode(PeriodePaie::MOIS);
                
                $heures = rand(10, 120);
                $montant = $heures * $coach->getTarifHoraire();
                $fiche->setMontantTotal($montant);
                
                $manager->persist($fiche);
            }
            
            // Fiches hebdomadaires
            for ($i = 0; $i < 3; $i++) {
                $fiche = new FicheDePaie();
                $fiche->setCoach($coach);
                $fiche->setPeriode(PeriodePaie::SEMAINE);
                
                $heures = rand(5, 30);
                $montant = $heures * $coach->getTarifHoraire();
                $fiche->setMontantTotal($montant);
                
                $manager->persist($fiche);
            }
        }
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

        // Responsable (Responsable) de test
        $responsable = new Responsable();
        $responsable->setNom('Martin');
        $responsable->setPrenom('Sophie');
        $responsable->setEmail('responsable@sportgest.fr');
        $responsable->setPassword($this->passwordHasher->hashPassword($responsable, 'password'));
        $manager->persist($responsable);

        $responsables[] = $responsable;

        return $responsables;
    }

    private function createAdmin(ObjectManager $manager): void
    {
        $admins = new Responsable();
        $admins->setNom('Admin');
        $admins->setPrenom('Admin');
        $admins->setEmail('admin@sportgest.fr');
        $admins->setRoles(['ROLE_ADMIN']);
        $admins->setPassword($this->passwordHasher->hashPassword($admins, 'password'));
        $manager->persist($admins);
    }
}