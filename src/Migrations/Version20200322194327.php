<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200322194327 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE evaluation CHANGE enseignant_id enseignant_id INT DEFAULT NULL, CHANGE groupe_id groupe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE groupe_etudiant CHANGE tree_root tree_root INT DEFAULT NULL, CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partie CHANGE evaluation_id evaluation_id INT DEFAULT NULL, CHANGE intitule intitule VARCHAR(50) DEFAULT NULL, CHANGE bareme bareme DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE points CHANGE etudiant_id etudiant_id INT DEFAULT NULL, CHANGE partie_id partie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE statut ADD slug VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE evaluation CHANGE enseignant_id enseignant_id INT DEFAULT NULL, CHANGE groupe_id groupe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE groupe_etudiant CHANGE tree_root tree_root INT DEFAULT NULL, CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partie CHANGE evaluation_id evaluation_id INT DEFAULT NULL, CHANGE intitule intitule VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE bareme bareme DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE points CHANGE etudiant_id etudiant_id INT DEFAULT NULL, CHANGE partie_id partie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE statut DROP slug');
    }
}
