<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250317142600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seance_specialite (seance_id INT NOT NULL, specialite_id INT NOT NULL, INDEX IDX_ED8860A4E3797A94 (seance_id), INDEX IDX_ED8860A42195E0F0 (specialite_id), PRIMARY KEY(seance_id, specialite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialite (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coach_specialite (coach_id INT NOT NULL, specialite_id INT NOT NULL, INDEX IDX_892B8B323C105691 (coach_id), INDEX IDX_892B8B322195E0F0 (specialite_id), PRIMARY KEY(coach_id, specialite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE seance_specialite ADD CONSTRAINT FK_ED8860A4E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance_specialite ADD CONSTRAINT FK_ED8860A42195E0F0 FOREIGN KEY (specialite_id) REFERENCES specialite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_specialite ADD CONSTRAINT FK_892B8B323C105691 FOREIGN KEY (coach_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_specialite ADD CONSTRAINT FK_892B8B322195E0F0 FOREIGN KEY (specialite_id) REFERENCES specialite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance DROP theme_seance');
        $this->addSql('ALTER TABLE utilisateur DROP specialites');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seance_specialite DROP FOREIGN KEY FK_ED8860A4E3797A94');
        $this->addSql('ALTER TABLE seance_specialite DROP FOREIGN KEY FK_ED8860A42195E0F0');
        $this->addSql('ALTER TABLE coach_specialite DROP FOREIGN KEY FK_892B8B323C105691');
        $this->addSql('ALTER TABLE coach_specialite DROP FOREIGN KEY FK_892B8B322195E0F0');
        $this->addSql('DROP TABLE seance_specialite');
        $this->addSql('DROP TABLE specialite');
        $this->addSql('DROP TABLE coach_specialite');
        $this->addSql('ALTER TABLE seance ADD theme_seance VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD specialites JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
