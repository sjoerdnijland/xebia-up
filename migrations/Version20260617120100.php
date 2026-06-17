<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create skill table and module_skill join table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE skill (
            id INT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(150) NOT NULL,
            category_id INT NOT NULL,
            capability_key VARCHAR(60) NOT NULL,
            domain_name VARCHAR(100) NOT NULL,
            domain_slug VARCHAR(60) NOT NULL,
            ring_slug VARCHAR(40) NOT NULL,
            ring_name VARCHAR(100) NOT NULL,
            view_scope VARCHAR(16) NOT NULL,
            position INT NOT NULL,
            descriptions JSON NOT NULL,
            UNIQUE INDEX UNIQ_5FBA94E1989D9B62 (slug),
            INDEX IDX_5FBA94E112469DE2 (category_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5FBA94E112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');

        $this->addSql('CREATE TABLE module_skill (
            module_id INT NOT NULL,
            skill_id INT NOT NULL,
            INDEX IDX_MODULE_SKILL_MODULE (module_id),
            INDEX IDX_MODULE_SKILL_SKILL (skill_id),
            PRIMARY KEY (module_id, skill_id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE module_skill ADD CONSTRAINT FK_MODULE_SKILL_MODULE FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_skill ADD CONSTRAINT FK_MODULE_SKILL_SKILL FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE module_skill DROP FOREIGN KEY FK_MODULE_SKILL_MODULE');
        $this->addSql('ALTER TABLE module_skill DROP FOREIGN KEY FK_MODULE_SKILL_SKILL');
        $this->addSql('DROP TABLE module_skill');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5FBA94E112469DE2');
        $this->addSql('DROP TABLE skill');
    }
}
