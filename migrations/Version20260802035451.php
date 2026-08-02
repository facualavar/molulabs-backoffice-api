<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802035451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on document_number to companies and users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3A28F2AE32 ON companies (document_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E928F2AE32 ON users (document_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8244AA3A28F2AE32');
        $this->addSql('DROP INDEX UNIQ_1483A5E928F2AE32');
    }
}
