<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260829201001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE match_event ADD external_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD external_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD external_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A6532C8A3DE9F75D7B0 ON player (organization_id, external_id)');
        $this->addSql('ALTER TABLE team ADD external_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F32C8A3DE9F75D7B0 ON team (organization_id, external_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE match_event DROP external_id');
        $this->addSql('ALTER TABLE matches DROP external_id');
        $this->addSql('DROP INDEX UNIQ_98197A6532C8A3DE9F75D7B0 ON player');
        $this->addSql('ALTER TABLE player DROP external_id');
        $this->addSql('DROP INDEX UNIQ_C4E0A61F32C8A3DE9F75D7B0 ON team');
        $this->addSql('ALTER TABLE team DROP external_id');
    }
}
