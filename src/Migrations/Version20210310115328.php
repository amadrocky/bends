<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210310115328 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signaled_discussions (id INT AUTO_INCREMENT NOT NULL, discussion_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, workflow_state VARCHAR(255) NOT NULL, INDEX IDX_169A595D1ADED311 (discussion_id), INDEX IDX_169A595DB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signaled_discussions ADD CONSTRAINT FK_169A595D1ADED311 FOREIGN KEY (discussion_id) REFERENCES discussions (id)');
        $this->addSql('ALTER TABLE signaled_discussions ADD CONSTRAINT FK_169A595DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE signaled_discussions');
    }
}
