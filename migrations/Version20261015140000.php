<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Name the export job file column after what it stores: the file name, not a path';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "export_jobs" RENAME COLUMN file_path TO file_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "export_jobs" RENAME COLUMN file_name TO file_path');
    }
}
