<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618084949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert 11 scrum.org training modules with category and role assignments.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $modules = [
            ['sc-psm',     'Professional Scrum Master (PSM)',                                        'Professional Scrum Master certification training (scrum.org).',                'competent',  16, 95],
            ['sc-psm-a',   'Professional Scrum Master - Advanced (PSM-A)',                           'Advanced Professional Scrum Master certification training (scrum.org).',        'proficient', 16, 96],
            ['sc-pspo',    'Professional Scrum Product Owner (PSPO)',                                'Professional Scrum Product Owner certification training (scrum.org).',          'competent',  16, 97],
            ['sc-pspo-a',  'Professional Scrum Product Owner - Advanced (PSPO-A)',                   'Advanced Professional Scrum Product Owner certification training (scrum.org).', 'proficient', 16, 98],
            ['sc-psk',     'Professional Scrum with Kanban (PSK)',                                   'Applying Kanban practices within a Scrum framework (scrum.org).',              'competent',  16, 99],
            ['sc-pal',     'Professional Agile Leadership (PAL)',                                    'Professional Agile Leadership certification training (scrum.org).',             'competent',  16, 100],
            ['sc-pal-ebm', 'Professional Agile Leadership - Evidence-Based Management (PAL-EBM)',    'Professional Agile Leadership with Evidence-Based Management (scrum.org).',     'proficient', 16, 101],
            ['sc-psm-ai',  'Professional Scrum Master - AI Essentials (PSM-AI)',                     'Applying AI in the Scrum Master role (scrum.org).',                             'competent',   8, 102],
            ['sc-pspo-ai', 'Professional Scrum Product Owner - AI Essentials (PSPO-AI)',             'Applying AI in the Product Owner role (scrum.org).',                            'competent',   8, 103],
            ['sc-psfs',    'Professional Scrum Facilitation Skills (PSFS)',                          'Mastering facilitation techniques for Scrum teams (scrum.org).',               'competent',   8, 104],
            ['sc-ppdv',    'Professional Product Discovery and Validation (PPDV)',                   'Techniques for discovering and validating product opportunities (scrum.org).',  'competent',   8, 105],
        ];

        foreach ($modules as [$slug, $title, $desc, $levelSlug, $duration, $position]) {
            $this->addSql(
                "INSERT IGNORE INTO module (slug, title, description, level_id, type_id, duration_hours, position)
                 SELECT :slug, :title, :desc, l.id, t.id, :duration, :position
                 FROM level l, module_type t
                 WHERE l.slug = :level AND t.slug = 'trainer-led'",
                ['slug' => $slug, 'title' => $title, 'desc' => $desc, 'level' => $levelSlug, 'duration' => $duration, 'position' => $position]
            );
        }

        $categoryAssignments = [
            'sc-psm'     => ['agile'],
            'sc-psm-a'   => ['agile'],
            'sc-pspo'    => ['agile', 'product'],
            'sc-pspo-a'  => ['agile', 'product'],
            'sc-psk'     => ['agile'],
            'sc-pal'     => ['agile', 'leadership'],
            'sc-pal-ebm' => ['agile', 'leadership'],
            'sc-psm-ai'  => ['agile', 'ai'],
            'sc-pspo-ai' => ['agile', 'ai'],
            'sc-psfs'    => ['agile'],
            'sc-ppdv'    => ['agile', 'product'],
        ];

        foreach ($categoryAssignments as $slug => $categories) {
            foreach ($categories as $cat) {
                $this->addSql(
                    "INSERT IGNORE INTO module_category (module_id, category_id)
                     SELECT m.id, c.id FROM module m, category c
                     WHERE m.slug = :slug AND c.slug = :cat",
                    ['slug' => $slug, 'cat' => $cat]
                );
            }
        }

        $roleAssignments = [
            'sc-psm'     => ['sm'],
            'sc-psm-a'   => ['sm'],
            'sc-pspo'    => ['po'],
            'sc-pspo-a'  => ['po'],
            'sc-psk'     => ['sm', 'po'],
            'sc-pal'     => ['pm', 'pjm'],
            'sc-pal-ebm' => ['pm', 'pjm'],
            'sc-psm-ai'  => ['sm'],
            'sc-pspo-ai' => ['po'],
            'sc-psfs'    => ['sm'],
            'sc-ppdv'    => ['po'],
        ];

        foreach ($roleAssignments as $slug => $roles) {
            foreach ($roles as $role) {
                $this->addSql(
                    "INSERT IGNORE INTO module_role (module_id, role_id)
                     SELECT m.id, r.id FROM module m, role r
                     WHERE m.slug = :slug AND r.slug = :role",
                    ['slug' => $slug, 'role' => $role]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE mr FROM module_role mr JOIN module m ON mr.module_id = m.id WHERE m.slug IN ('sc-psm','sc-psm-a','sc-pspo','sc-pspo-a','sc-psk','sc-pal','sc-pal-ebm','sc-psm-ai','sc-pspo-ai','sc-psfs','sc-ppdv')");
        $this->addSql("DELETE mc FROM module_category mc JOIN module m ON mc.module_id = m.id WHERE m.slug IN ('sc-psm','sc-psm-a','sc-pspo','sc-pspo-a','sc-psk','sc-pal','sc-pal-ebm','sc-psm-ai','sc-pspo-ai','sc-psfs','sc-ppdv')");
        $this->addSql("DELETE FROM module WHERE slug IN ('sc-psm','sc-psm-a','sc-pspo','sc-pspo-a','sc-psk','sc-pal','sc-pal-ebm','sc-psm-ai','sc-pspo-ai','sc-psfs','sc-ppdv')");
    }
}
