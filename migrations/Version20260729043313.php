<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729043313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add document_type, document_number and phone to companies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies ADD document_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE companies ADD document_number VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE companies ADD phone VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "companies" DROP document_type');
        $this->addSql('ALTER TABLE "companies" DROP document_number');
        $this->addSql('ALTER TABLE "companies" DROP phone');
    }
}
