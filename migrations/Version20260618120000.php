<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create client and journey tables (promotes from session-stored value objects).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE client (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, created_at DATETIME NOT NULL)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_C74404555E237E06 ON client (name)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_C7440455989D9B62 ON client (slug)");

        $this->addSql("CREATE TABLE journey (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, audience VARCHAR(120) DEFAULT '' NOT NULL, module_slugs CLOB DEFAULT '[]' NOT NULL, created_at DATETIME NOT NULL, position INTEGER DEFAULT 0 NOT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_C816C6A219EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)");
        $this->addSql("CREATE INDEX IDX_JOURNEY_CLIENT ON journey (client_id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE journey");
        $this->addSql("DROP TABLE client");
    }
}
