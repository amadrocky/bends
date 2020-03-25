<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200325104009 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE offers (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, workflow_state VARCHAR(255) NOT NULL, pictures JSON DEFAULT NULL, zip_code INT NOT NULL, city VARCHAR(255) NOT NULL, phone_number INT NOT NULL, phone_visible TINYINT(1) NOT NULL, context VARCHAR(255) NOT NULL, INDEX IDX_DA460427C54C8C93 (type_id), INDEX IDX_DA460427B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, date_of_birth DATE NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, created_at DATE NOT NULL, workflow_state VARCHAR(255) NOT NULL, profil_image VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, offers_id INT NOT NULL, name VARCHAR(255) NOT NULL, workflow_state VARCHAR(255) NOT NULL, INDEX IDX_3AF34668A090B42E (offers_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668A090B42E FOREIGN KEY (offers_id) REFERENCES offers (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668A090B42E');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427B03A8386');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427C54C8C93');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE type');
        $this->addSql('DROP TABLE categories');
    }
}
