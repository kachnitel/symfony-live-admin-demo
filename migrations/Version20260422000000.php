<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add archived boolean field to parts table for archive/soft-delete feature (v0.9).
 */
final class Version20260422000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archived column to parts table for archive/soft-delete feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parts ADD COLUMN archived BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parts DROP COLUMN archived');
    }
}
