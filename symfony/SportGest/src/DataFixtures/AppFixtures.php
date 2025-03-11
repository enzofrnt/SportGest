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

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Ajout d'un coach
        $coach = new Coach();
        $coach->setNom('Dupont');
        $coach->setPrenom('Paul');
        $coach->setEmail('paul.dupont@example.com');
        $coach->setMotDePasse($this->passwordHasher->hashPassword($coach, 'password'));
        $coach->setRole('Coach');
        $coach->setSpecialites(['Cardio', 'Musculation']);
        $coach->setTarifHoraire(50.0);
        $manager->persist($coach);

        // Ajout d'un sportif
        $sportif = new Sportif();
        $sportif->setNom('Martin');
        $sportif->setPrenom('Julie');
        $sportif->setEmail('julie.martin@example.com');
        $sportif->setMotDePasse($this->passwordHasher->hashPassword($sportif, 'password'));
        $sportif->setRole('Sportif');
        $sportif->setDateInscription(new \DateTime());
        $sportif->setNiveauSportif('Intermédiaire');
        $manager->persist($sportif);

        // Ajout d'une séance
        $seance = new Seance();
        $seance->setDateHeure(new \DateTime('+1 day'));
        $seance->setTypeSeance('Duo');
        $seance->setThemeSeance('Fitness');
        $seance->setCoach($coach);
        $seance->setStatut('Prévue');
        $seance->setNiveauSeance('Débutant');
        $manager->persist($seance);

        $manager->flush();
    }
}