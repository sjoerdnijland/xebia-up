<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add is_active flag to module for soft-delete support and deactivate
 * the two AI Essentials scrum.org modules (sc-psm-ai, sc-pspo-ai).
 */
final class Version20260707180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add module.is_active column and soft-delete sc-psm-ai and sc-pspo-ai.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->executeQuery("SHOW COLUMNS FROM module LIKE 'is_active'")->fetchAllAssociative();
        if (empty($columns)) {
            $this->addSql("ALTER TABLE module ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
        }

        $this->addSql("UPDATE module SET is_active = 0 WHERE slug IN ('sc-psm-ai', 'sc-pspo-ai')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE module DROP COLUMN is_active");
    }
}
