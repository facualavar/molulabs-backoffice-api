<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802011910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add document_number and document_type to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD document_number VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE users ADD document_type VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" DROP document_number');
        $this->addSql('ALTER TABLE "users" DROP document_type');
    }
}
