<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609095740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, guest_name VARCHAR(200) NOT NULL, guest_email VARCHAR(200) NOT NULL, guest_phone VARCHAR(50) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, session_id INT NOT NULL, INDEX IDX_E00CEDDE613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, tag VARCHAR(200) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, depth INT NOT NULL, color_hex VARCHAR(10) NOT NULL, tint_hex VARCHAR(10) NOT NULL, blurb VARCHAR(300) NOT NULL, UNIQUE INDEX UNIQ_9AEACC13989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(200) NOT NULL, slug VARCHAR(200) NOT NULL, description LONGTEXT NOT NULL, position INT NOT NULL, level_id INT NOT NULL, UNIQUE INDEX UNIQ_C242628989D9B62 (slug), INDEX IDX_C2426285FB14BA7 (level_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module_category (module_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_628CCA3FAFC2B591 (module_id), INDEX IDX_628CCA3F12469DE2 (category_id), PRIMARY KEY (module_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module_role (module_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_ED55CF66AFC2B591 (module_id), INDEX IDX_ED55CF66D60322AC (role_id), PRIMARY KEY (module_id, role_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module_objective (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(500) NOT NULL, position INT NOT NULL, module_id INT NOT NULL, INDEX IDX_E3F7EEC9AFC2B591 (module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, short_code VARCHAR(10) NOT NULL, slug VARCHAR(100) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_57698A6A989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, format VARCHAR(255) NOT NULL, location VARCHAR(200) DEFAULT NULL, capacity INT NOT NULL, module_id INT NOT NULL, INDEX IDX_D044D5D4AFC2B591 (module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426285FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE module_category ADD CONSTRAINT FK_628CCA3FAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_category ADD CONSTRAINT FK_628CCA3F12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_role ADD CONSTRAINT FK_ED55CF66AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_role ADD CONSTRAINT FK_ED55CF66D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_objective ADD CONSTRAINT FK_E3F7EEC9AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE613FECDF');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426285FB14BA7');
        $this->addSql('ALTER TABLE module_category DROP FOREIGN KEY FK_628CCA3FAFC2B591');
        $this->addSql('ALTER TABLE module_category DROP FOREIGN KEY FK_628CCA3F12469DE2');
        $this->addSql('ALTER TABLE module_role DROP FOREIGN KEY FK_ED55CF66AFC2B591');
        $this->addSql('ALTER TABLE module_role DROP FOREIGN KEY FK_ED55CF66D60322AC');
        $this->addSql('ALTER TABLE module_objective DROP FOREIGN KEY FK_E3F7EEC9AFC2B591');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4AFC2B591');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE level');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE module_category');
        $this->addSql('DROP TABLE module_role');
        $this->addSql('DROP TABLE module_objective');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE session');
    }
}
