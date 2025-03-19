<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250319160726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercice (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, duree_estimee INT NOT NULL, difficulte VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fiche_de_paie (id INT AUTO_INCREMENT NOT NULL, coach_id INT NOT NULL, periode VARCHAR(255) NOT NULL, montant_total DOUBLE PRECISION NOT NULL, INDEX IDX_B3236E133C105691 (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, coach_id INT NOT NULL, theme_id INT DEFAULT NULL, date_heure DATETIME NOT NULL, type_seance VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, niveau_seance VARCHAR(255) NOT NULL, INDEX IDX_DF7DFD0E3C105691 (coach_id), INDEX IDX_DF7DFD0E59027487 (theme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seance_sportif (seance_id INT NOT NULL, sportif_id INT NOT NULL, INDEX IDX_AFE1A4CE3797A94 (seance_id), INDEX IDX_AFE1A4CFFB7083B (sportif_id), PRIMARY KEY(seance_id, sportif_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seance_exercice (seance_id INT NOT NULL, exercice_id INT NOT NULL, INDEX IDX_8A34735E3797A94 (seance_id), INDEX IDX_8A3473589D40298 (exercice_id), PRIMARY KEY(seance_id, exercice_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialite (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', role VARCHAR(255) NOT NULL, tarif_horaire DOUBLE PRECISION DEFAULT NULL, date_inscription DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', niveau_sportif VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coach_specialite (coach_id INT NOT NULL, specialite_id INT NOT NULL, INDEX IDX_892B8B323C105691 (coach_id), INDEX IDX_892B8B322195E0F0 (specialite_id), PRIMARY KEY(coach_id, specialite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fiche_de_paie ADD CONSTRAINT FK_B3236E133C105691 FOREIGN KEY (coach_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E3C105691 FOREIGN KEY (coach_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E59027487 FOREIGN KEY (theme_id) REFERENCES specialite (id)');
        $this->addSql('ALTER TABLE seance_sportif ADD CONSTRAINT FK_AFE1A4CE3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance_sportif ADD CONSTRAINT FK_AFE1A4CFFB7083B FOREIGN KEY (sportif_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance_exercice ADD CONSTRAINT FK_8A34735E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance_exercice ADD CONSTRAINT FK_8A3473589D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_specialite ADD CONSTRAINT FK_892B8B323C105691 FOREIGN KEY (coach_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_specialite ADD CONSTRAINT FK_892B8B322195E0F0 FOREIGN KEY (specialite_id) REFERENCES specialite (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fiche_de_paie DROP FOREIGN KEY FK_B3236E133C105691');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E3C105691');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E59027487');
        $this->addSql('ALTER TABLE seance_sportif DROP FOREIGN KEY FK_AFE1A4CE3797A94');
        $this->addSql('ALTER TABLE seance_sportif DROP FOREIGN KEY FK_AFE1A4CFFB7083B');
        $this->addSql('ALTER TABLE seance_exercice DROP FOREIGN KEY FK_8A34735E3797A94');
        $this->addSql('ALTER TABLE seance_exercice DROP FOREIGN KEY FK_8A3473589D40298');
        $this->addSql('ALTER TABLE coach_specialite DROP FOREIGN KEY FK_892B8B323C105691');
        $this->addSql('ALTER TABLE coach_specialite DROP FOREIGN KEY FK_892B8B322195E0F0');
        $this->addSql('DROP TABLE exercice');
        $this->addSql('DROP TABLE fiche_de_paie');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE seance_sportif');
        $this->addSql('DROP TABLE seance_exercice');
        $this->addSql('DROP TABLE specialite');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE coach_specialite');
    }
}
