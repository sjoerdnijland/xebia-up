<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename levels: foundation→foundational, practitioner→competent, professional→proficient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE level SET slug = 'foundational', name = 'Foundational' WHERE slug = 'foundation'");
        $this->addSql("UPDATE level SET slug = 'competent',    name = 'Competent'    WHERE slug = 'practitioner'");
        $this->addSql("UPDATE level SET slug = 'proficient',   name = 'Proficient'   WHERE slug = 'professional'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE level SET slug = 'foundation',   name = 'Foundation'   WHERE slug = 'foundational'");
        $this->addSql("UPDATE level SET slug = 'practitioner', name = 'Practitioner' WHERE slug = 'competent'");
        $this->addSql("UPDATE level SET slug = 'professional', name = 'Professional' WHERE slug = 'proficient'");
    }
}
