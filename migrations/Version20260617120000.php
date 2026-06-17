<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename levels: foundational→foundation, intermediate→practitioner, advanced→professional';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE level SET slug = 'foundation',   name = 'Foundation',   blurb = 'Understands the basics and can take part with guidance.'             WHERE slug = 'foundational'");
        $this->addSql("UPDATE level SET slug = 'practitioner', name = 'Practitioner', blurb = 'Applies the skill in everyday situations, with some support.'       WHERE slug = 'intermediate'");
        $this->addSql("UPDATE level SET slug = 'professional', name = 'Professional', blurb = 'Works independently in varied, complex situations; analyses and judges.' WHERE slug = 'advanced'");
        $this->addSql("UPDATE level SET                       name = 'Expert',       blurb = 'Sets direction, designs new approaches, and coaches others.'        WHERE slug = 'expert'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE level SET slug = 'foundational', name = 'Foundational', blurb = 'Universal literacy and role-specific entry points.'                  WHERE slug = 'foundation'");
        $this->addSql("UPDATE level SET slug = 'intermediate', name = 'Intermediate', blurb = 'Where roles begin to diverge meaningfully.'                          WHERE slug = 'practitioner'");
        $this->addSql("UPDATE level SET slug = 'advanced',     name = 'Advanced',     blurb = 'The applied craft layer — mostly cross-role.'                       WHERE slug = 'professional'");
    }
}
