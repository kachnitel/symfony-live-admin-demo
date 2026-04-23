<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Part;
use Kachnitel\AdminBundle\Attribute\Admin;
use PHPUnit\Framework\TestCase;

/**
 * @group archive
 */
class PartArchiveTest extends TestCase
{
    public function testPartHasArchivedField(): void
    {
        $part = new Part();

        // Property must exist and default to false
        $this->assertFalse($part->isArchived());
    }

    public function testPartArchivedCanBeSet(): void
    {
        $part = new Part();
        $part->setArchived(true);

        $this->assertTrue($part->isArchived());
    }

    public function testPartArchivedDefaultIsFalse(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $property = $reflection->getProperty('archived');
        $defaults = $reflection->getDefaultProperties();

        $this->assertArrayHasKey('archived', $defaults);
        $this->assertFalse($defaults['archived']);
    }

    public function testPartAdminAttributeHasArchiveExpression(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'Part should have #[Admin] attribute');

        /** @var Admin $adminAttr */
        $adminAttr = $attributes[0]->newInstance();

        $this->assertSame(
            'item.archived',
            $adminAttr->getArchiveExpression(),
            'Part #[Admin] should have archiveExpression set to "item.archived"'
        );
    }
}
