<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\CapabilityMap;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create module_capability table (per-category capability key per module)
 * and backfill from the CapabilityMap PHP consts so edit-mode can mutate
 * capabilities at runtime.
 */
final class Version20260707190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create module_capability + backfill from CapabilityMap consts.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $tables = $this->connection->executeQuery("SHOW TABLES LIKE 'module_capability'")->fetchAllAssociative();
        if (empty($tables)) {
            $this->addSql("CREATE TABLE module_capability (
                id INTEGER NOT NULL AUTO_INCREMENT,
                module_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                capability_key VARCHAR(80) NOT NULL,
                PRIMARY KEY(id),
                UNIQUE KEY uniq_module_category (module_id, category_id),
                CONSTRAINT fk_mc_module FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE,
                CONSTRAINT fk_mc_category FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            )");
        }

        // Backfill from the (now legacy) CapabilityMap consts.
        // Each module gets one row per category it is in. Priority:
        //   1. MODULE_CAT_MAP override for that (slug, category)
        //   2. MODULE_MAP default capability (only inserted for the primary category if it matches)
        $rows = $this->connection->executeQuery(
            "SELECT m.id AS mid, m.slug AS mslug, c.id AS cid, c.slug AS cslug
             FROM module m
             JOIN module_category mc ON mc.module_id = m.id
             JOIN category c ON c.id = mc.category_id"
        )->fetchAllAssociative();

        $catCaps = CapabilityMap::CAPABILITY_KEYS_BY_CATEGORY;
        $catMap = CapabilityMap::LEGACY_MODULE_CAT_MAP;
        $defaultMap = CapabilityMap::LEGACY_MODULE_MAP;

        foreach ($rows as $row) {
            $mslug = $row['mslug'];
            $cslug = $row['cslug'];
            $mid = (int) $row['mid'];
            $cid = (int) $row['cid'];

            // Priority: per-category override, then default, then null (skip).
            $key = $catMap[$mslug][$cslug] ?? null;
            if ($key === null) {
                $default = $defaultMap[$mslug] ?? null;
                if ($default !== null && in_array($default, $catCaps[$cslug] ?? [], true)) {
                    $key = $default;
                }
            }

            if ($key === null) {
                continue;
            }

            $this->addSql(
                "INSERT IGNORE INTO module_capability (module_id, category_id, capability_key)
                 VALUES (:mid, :cid, :key)",
                ['mid' => $mid, 'cid' => $cid, 'key' => $key]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE module_capability");
    }
}
