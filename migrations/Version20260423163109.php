<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423163109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__bicycles AS SELECT id, brand, model, color, year, created_at FROM bicycles');
        $this->addSql('DROP TABLE bicycles');
        $this->addSql('CREATE TABLE bicycles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, brand VARCHAR(100) NOT NULL, model VARCHAR(100) NOT NULL, color VARCHAR(50) NOT NULL, year INTEGER NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO bicycles (id, brand, model, color, year, created_at) SELECT id, brand, model, color, year, created_at FROM __temp__bicycles');
        $this->addSql('DROP TABLE __temp__bicycles');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parts AS SELECT id, name, manufacturer, price, bicycle_id, created_at, archived FROM parts');
        $this->addSql('DROP TABLE parts');
        $this->addSql('CREATE TABLE parts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, manufacturer VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, bicycle_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, archived BOOLEAN NOT NULL, CONSTRAINT FK_6940A7FEA69645CF FOREIGN KEY (bicycle_id) REFERENCES bicycles (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO parts (id, name, manufacturer, price, bicycle_id, created_at, archived) SELECT id, name, manufacturer, price, bicycle_id, created_at, archived FROM __temp__parts');
        $this->addSql('DROP TABLE __temp__parts');
        $this->addSql('CREATE INDEX IDX_6940A7FEA69645CF ON parts (bicycle_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, password, name, active, created_at, last_login_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO users (id, email, password, name, active, created_at, last_login_at) SELECT id, email, password, name, active, created_at, last_login_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__bicycles AS SELECT id, brand, model, color, year, created_at FROM bicycles');
        $this->addSql('DROP TABLE bicycles');
        $this->addSql('CREATE TABLE bicycles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, brand VARCHAR(100) NOT NULL, model VARCHAR(100) NOT NULL, color VARCHAR(50) NOT NULL, year INTEGER NOT NULL, created_at DATETIME DEFAULT \'2025-01-01 00:00:00\' NOT NULL)');
        $this->addSql('INSERT INTO bicycles (id, brand, model, color, year, created_at) SELECT id, brand, model, color, year, created_at FROM __temp__bicycles');
        $this->addSql('DROP TABLE __temp__bicycles');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parts AS SELECT id, name, manufacturer, price, created_at, archived, bicycle_id FROM parts');
        $this->addSql('DROP TABLE parts');
        $this->addSql('CREATE TABLE parts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, manufacturer VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME DEFAULT \'2025-01-01 00:00:00\' NOT NULL, archived BOOLEAN DEFAULT 0 NOT NULL, bicycle_id INTEGER DEFAULT NULL, CONSTRAINT FK_6940A7FEA69645CF FOREIGN KEY (bicycle_id) REFERENCES bicycles (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO parts (id, name, manufacturer, price, created_at, archived, bicycle_id) SELECT id, name, manufacturer, price, created_at, archived, bicycle_id FROM __temp__parts');
        $this->addSql('DROP TABLE __temp__parts');
        $this->addSql('CREATE INDEX IDX_6940A7FEA69645CF ON parts (bicycle_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, password, name, active, created_at, last_login_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME DEFAULT \'2025-01-01 00:00:00\' NOT NULL, last_login_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO users (id, email, password, name, active, created_at, last_login_at) SELECT id, email, password, name, active, created_at, last_login_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }
}
