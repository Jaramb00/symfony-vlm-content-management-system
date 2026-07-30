<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730134509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE media_file SET path = SUBSTRING_INDEX(SUBSTRING_INDEX(path, '/', -1), '\\\\', -1)");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();

    }
}
