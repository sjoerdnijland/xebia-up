<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618083454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add duration_hours to module; add scrum.org modules with full titles, durations, and cross-category assignments; remove Facilitating Agile Ceremonies; rename Facilitating Events.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Schema
        $this->addSql('ALTER TABLE module ADD COLUMN duration_hours INTEGER NOT NULL DEFAULT 4');

        // Scrum.org module titles
        $this->addSql("UPDATE module SET title = 'Professional Scrum Master (PSM)' WHERE slug = 'sc-psm'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Master - Advanced (PSM-A)' WHERE slug = 'sc-psm-a'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Product Owner (PSPO)' WHERE slug = 'sc-pspo'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Product Owner - Advanced (PSPO-A)' WHERE slug = 'sc-pspo-a'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum with Kanban (PSK)' WHERE slug = 'sc-psk'");
        $this->addSql("UPDATE module SET title = 'Professional Agile Leadership (PAL)' WHERE slug = 'sc-pal'");
        $this->addSql("UPDATE module SET title = 'Professional Agile Leadership - Evidence-Based Management (PAL-EBM)' WHERE slug = 'sc-pal-ebm'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Master - AI Essentials (PSM-AI)' WHERE slug = 'sc-psm-ai'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Product Owner - AI Essentials (PSPO-AI)' WHERE slug = 'sc-pspo-ai'");
        $this->addSql("UPDATE module SET title = 'Professional Scrum Facilitation Skills (PSFS)' WHERE slug = 'sc-psfs'");
        $this->addSql("UPDATE module SET title = 'Professional Product Discovery and Validation (PPDV)' WHERE slug = 'sc-ppdv'");

        // Durations
        $this->addSql("UPDATE module SET duration_hours = 2 WHERE slug LIKE 'ai-%'");
        $this->addSql("UPDATE module SET duration_hours = 16 WHERE slug IN ('sc-psm','sc-pspo','sc-psm-a','sc-pspo-a','sc-pal','sc-pal-ebm','sc-psk')");
        $this->addSql("UPDATE module SET duration_hours = 8 WHERE slug IN ('sc-psfs','sc-ppdv','sc-psm-ai','sc-pspo-ai')");

        // Cross-category assignments for scrum.org modules
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-pspo' AND c.slug = 'product'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-pspo-a' AND c.slug = 'product'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-ppdv' AND c.slug = 'product'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-pal' AND c.slug = 'leadership'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-pal-ebm' AND c.slug = 'leadership'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-psm-ai' AND c.slug = 'ai'");
        $this->addSql("INSERT IGNORE INTO module_category (module_id, category_id) SELECT m.id, c.id FROM module m, category c WHERE m.slug = 'sc-pspo-ai' AND c.slug = 'ai'");

        // Remove Facilitating Agile Ceremonies (ag-i-2)
        $this->addSql("DELETE FROM booking WHERE session_id IN (SELECT id FROM session WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2'))");
        $this->addSql("DELETE FROM session WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2')");
        $this->addSql("DELETE FROM module_category WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2')");
        $this->addSql("DELETE FROM module_role WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2')");
        $this->addSql("DELETE FROM module_objective WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2')");
        $this->addSql("DELETE FROM module_skill WHERE module_id = (SELECT id FROM module WHERE slug = 'ag-i-2')");
        $this->addSql("DELETE FROM module WHERE slug = 'ag-i-2'");

        // Rename sm-i-1
        $this->addSql("UPDATE module SET title = 'Facilitating Events' WHERE slug = 'sm-i-1'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE module DROP COLUMN duration_hours');

        $this->addSql("DELETE FROM module_category WHERE module_id IN (SELECT id FROM module WHERE slug IN ('sc-pspo','sc-pspo-a','sc-ppdv')) AND category_id = (SELECT id FROM category WHERE slug = 'product')");
        $this->addSql("DELETE FROM module_category WHERE module_id IN (SELECT id FROM module WHERE slug IN ('sc-pal','sc-pal-ebm')) AND category_id = (SELECT id FROM category WHERE slug = 'leadership')");
        $this->addSql("DELETE FROM module_category WHERE module_id IN (SELECT id FROM module WHERE slug IN ('sc-psm-ai','sc-pspo-ai')) AND category_id = (SELECT id FROM category WHERE slug = 'ai')");
    }
}
