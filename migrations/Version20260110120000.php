<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add datetime fields to User, Bicycle, and Part entities for v0.2.0 demo features.
 */
final class Version20260110120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and last_login_at fields to users, created_at to bicycles and parts';
    }

    public function up(Schema $schema): void
    {
        // SQLite doesn't support DEFAULT CURRENT_TIMESTAMP in ALTER TABLE
        // Use a literal default date instead
        $defaultDate = '2025-01-01 00:00:00';

        // Users table
        $this->addSql("ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT '$defaultDate'");
        $this->addSql('ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL');

        // Bicycles table
        $this->addSql("ALTER TABLE bicycles ADD COLUMN created_at DATETIME NOT NULL DEFAULT '$defaultDate'");

        // Parts table
        $this->addSql("ALTER TABLE parts ADD COLUMN created_at DATETIME NOT NULL DEFAULT '$defaultDate'");
    }

    public function down(Schema $schema): void
    {
        // SQLite doesn't support DROP COLUMN directly, need to recreate tables
        // For simplicity, we'll use ALTER TABLE DROP COLUMN which works in SQLite 3.35+

        $this->addSql('ALTER TABLE users DROP COLUMN created_at');
        $this->addSql('ALTER TABLE users DROP COLUMN last_login_at');
        $this->addSql('ALTER TABLE bicycles DROP COLUMN created_at');
        $this->addSql('ALTER TABLE parts DROP COLUMN created_at');
    }
}
