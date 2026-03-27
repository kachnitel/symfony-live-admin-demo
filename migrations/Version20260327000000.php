<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add archived (soft-delete) flag to parts table to demonstrate the archive feature.
 */
final class Version20260327000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archived boolean column to parts to support archive/soft-delete filtering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE parts ADD COLUMN archived BOOLEAN NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        // SQLite 3.35+ supports DROP COLUMN
        $this->addSql('ALTER TABLE parts DROP COLUMN archived');
    }
}
