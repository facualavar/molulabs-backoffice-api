<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803003229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on email to companies and users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3AE7927C74 ON companies (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8244AA3AE7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
    }
}
