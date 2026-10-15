<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015125205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the user role in its own column instead of a JSON list';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" ADD role VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE "users" DROP roles');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" ADD roles JSONB NOT NULL');
        $this->addSql('ALTER TABLE "users" DROP role');
    }
}
