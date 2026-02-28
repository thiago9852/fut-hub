<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260829183741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE championship (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, format VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, UNIQUE INDEX UNIQ_EBADDE6A989D9B62 (slug), INDEX IDX_EBADDE6A32C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_log (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, records_processed INT NOT NULL, records_created INT NOT NULL, records_updated INT NOT NULL, records_failed INT NOT NULL, errors JSON DEFAULT NULL, created_at DATETIME NOT NULL, organization_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1B52C84532C8A3DE (organization_id), INDEX IDX_1B52C845A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE match_event (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, minute INT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, match_id INT NOT NULL, team_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_85C475062ABEACD6 (match_id), INDEX IDX_85C47506296CD8AE (team_id), INDEX IDX_85C4750699E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE match_lineup (id INT AUTO_INCREMENT NOT NULL, starter TINYINT NOT NULL, position VARCHAR(50) DEFAULT NULL, shirt_number INT DEFAULT NULL, entered_at INT DEFAULT NULL, left_at INT DEFAULT NULL, match_id INT NOT NULL, team_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_6C11F7CB2ABEACD6 (match_id), INDEX IDX_6C11F7CB296CD8AE (team_id), INDEX IDX_6C11F7CB99E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE matches (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME DEFAULT NULL, home_score INT DEFAULT NULL, away_score INT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, season_id INT NOT NULL, round_id INT NOT NULL, stadium_id INT DEFAULT NULL, home_team_id INT NOT NULL, away_team_id INT NOT NULL, INDEX IDX_62615BA4EC001D1 (season_id), INDEX IDX_62615BAA6005CA0 (round_id), INDEX IDX_62615BA7E860E36 (stadium_id), INDEX IDX_62615BA9C4C13F6 (home_team_id), INDEX IDX_62615BA45185D02 (away_team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, state VARCHAR(2) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, birth_date DATETIME DEFAULT NULL, position VARCHAR(50) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, UNIQUE INDEX UNIQ_98197A65989D9B62 (slug), INDEX IDX_98197A6532C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE round (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, name VARCHAR(255) DEFAULT NULL, scheduled_date DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, season_id INT NOT NULL, INDEX IDX_C5EEEA344EC001D1 (season_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, year INT NOT NULL, status VARCHAR(20) NOT NULL, championship_id INT NOT NULL, INDEX IDX_F0E45BA994DDBCE9 (championship_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stadium (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, capacity INT DEFAULT NULL, organization_id INT NOT NULL, INDEX IDX_E604044F32C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, short_name VARCHAR(20) DEFAULT NULL, slug VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, founded_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, UNIQUE INDEX UNIQ_C4E0A61F989D9B62 (slug), INDEX IDX_C4E0A61F32C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team_player (id INT AUTO_INCREMENT NOT NULL, shirt_number INT DEFAULT NULL, started_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, team_id INT NOT NULL, player_id INT NOT NULL, season_id INT NOT NULL, INDEX IDX_EE023DBC296CD8AE (team_id), INDEX IDX_EE023DBC99E6F5DF (player_id), INDEX IDX_EE023DBC4EC001D1 (season_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT DEFAULT NULL, INDEX IDX_8D93D64932C8A3DE (organization_id), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE championship ADD CONSTRAINT FK_EBADDE6A32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE import_log ADD CONSTRAINT FK_1B52C84532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE import_log ADD CONSTRAINT FK_1B52C845A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE match_event ADD CONSTRAINT FK_85C475062ABEACD6 FOREIGN KEY (match_id) REFERENCES matches (id)');
        $this->addSql('ALTER TABLE match_event ADD CONSTRAINT FK_85C47506296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE match_event ADD CONSTRAINT FK_85C4750699E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE match_lineup ADD CONSTRAINT FK_6C11F7CB2ABEACD6 FOREIGN KEY (match_id) REFERENCES matches (id)');
        $this->addSql('ALTER TABLE match_lineup ADD CONSTRAINT FK_6C11F7CB296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE match_lineup ADD CONSTRAINT FK_6C11F7CB99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAA6005CA0 FOREIGN KEY (round_id) REFERENCES round (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA7E860E36 FOREIGN KEY (stadium_id) REFERENCES stadium (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA9C4C13F6 FOREIGN KEY (home_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA45185D02 FOREIGN KEY (away_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A6532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE round ADD CONSTRAINT FK_C5EEEA344EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA994DDBCE9 FOREIGN KEY (championship_id) REFERENCES championship (id)');
        $this->addSql('ALTER TABLE stadium ADD CONSTRAINT FK_E604044F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE team_player ADD CONSTRAINT FK_EE023DBC296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE team_player ADD CONSTRAINT FK_EE023DBC99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE team_player ADD CONSTRAINT FK_EE023DBC4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE championship DROP FOREIGN KEY FK_EBADDE6A32C8A3DE');
        $this->addSql('ALTER TABLE import_log DROP FOREIGN KEY FK_1B52C84532C8A3DE');
        $this->addSql('ALTER TABLE import_log DROP FOREIGN KEY FK_1B52C845A76ED395');
        $this->addSql('ALTER TABLE match_event DROP FOREIGN KEY FK_85C475062ABEACD6');
        $this->addSql('ALTER TABLE match_event DROP FOREIGN KEY FK_85C47506296CD8AE');
        $this->addSql('ALTER TABLE match_event DROP FOREIGN KEY FK_85C4750699E6F5DF');
        $this->addSql('ALTER TABLE match_lineup DROP FOREIGN KEY FK_6C11F7CB2ABEACD6');
        $this->addSql('ALTER TABLE match_lineup DROP FOREIGN KEY FK_6C11F7CB296CD8AE');
        $this->addSql('ALTER TABLE match_lineup DROP FOREIGN KEY FK_6C11F7CB99E6F5DF');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA4EC001D1');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAA6005CA0');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA7E860E36');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA9C4C13F6');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA45185D02');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A6532C8A3DE');
        $this->addSql('ALTER TABLE round DROP FOREIGN KEY FK_C5EEEA344EC001D1');
        $this->addSql('ALTER TABLE season DROP FOREIGN KEY FK_F0E45BA994DDBCE9');
        $this->addSql('ALTER TABLE stadium DROP FOREIGN KEY FK_E604044F32C8A3DE');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F32C8A3DE');
        $this->addSql('ALTER TABLE team_player DROP FOREIGN KEY FK_EE023DBC296CD8AE');
        $this->addSql('ALTER TABLE team_player DROP FOREIGN KEY FK_EE023DBC99E6F5DF');
        $this->addSql('ALTER TABLE team_player DROP FOREIGN KEY FK_EE023DBC4EC001D1');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('DROP TABLE championship');
        $this->addSql('DROP TABLE import_log');
        $this->addSql('DROP TABLE match_event');
        $this->addSql('DROP TABLE match_lineup');
        $this->addSql('DROP TABLE matches');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE round');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE stadium');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_player');
        $this->addSql('DROP TABLE user');
    }
}
