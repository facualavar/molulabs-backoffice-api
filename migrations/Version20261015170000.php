<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record when a worker started an export, to detect the ones that never finished';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "export_jobs" ADD started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "export_jobs" DROP started_at');
    }
}
