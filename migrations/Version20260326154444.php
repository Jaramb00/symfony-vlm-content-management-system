<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326154444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE airesponse (id INT AUTO_INCREMENT NOT NULL, raw_response JSON NOT NULL, processed_content LONGTEXT DEFAULT NULL, model_used VARCHAR(100) NOT NULL, latency_ms INT NOT NULL, created_at DATETIME NOT NULL, content_request_id INT NOT NULL, UNIQUE INDEX UNIQ_7636E455DB470E57 (content_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_file (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, path VARCHAR(500) NOT NULL, size INT NOT NULL, created_at DATETIME NOT NULL, content_request_id INT NOT NULL, INDEX IDX_4FD8E9C3DB470E57 (content_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE airesponse ADD CONSTRAINT FK_7636E455DB470E57 FOREIGN KEY (content_request_id) REFERENCES content_request (id)');
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C3DB470E57 FOREIGN KEY (content_request_id) REFERENCES content_request (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE airesponse DROP FOREIGN KEY FK_7636E455DB470E57');
        $this->addSql('ALTER TABLE media_file DROP FOREIGN KEY FK_4FD8E9C3DB470E57');
        $this->addSql('DROP TABLE airesponse');
        $this->addSql('DROP TABLE media_file');
    }
}
