<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use Kachnitel\AdminBundle\Attribute\Admin;
use Kachnitel\AdminBundle\Attribute\AdminAction;
use Kachnitel\AdminBundle\Attribute\AdminColumn;
use Kachnitel\AdminBundle\Attribute\AdminColumnGroup;
use Kachnitel\AdminBundle\Attribute\AdminCustomColumn;
use Kachnitel\AdminBundle\Attribute\ColumnFilter;
use Kachnitel\AdminBundle\Attribute\ColumnPermission;
use Kachnitel\AdminBundle\Security\AdminEntityVoter;
use Kachnitel\AdminBundle\Service\AttributeHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bicycle::class)]
#[CoversClass(Part::class)]
#[CoversClass(User::class)]
#[Group('entity-attributes')]
class EntityAttributeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // User
    // -------------------------------------------------------------------------

    public function testUserHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'User entity should have Admin attribute');

        $adminAttr = $attributes[0]->newInstance();
        $this->assertEquals('person', $adminAttr->getIcon());
        $this->assertTrue($adminAttr->isEnableColumnVisibility());
    }

    public function testUserLastLoginAtHasColumnPermission(): void
    {
        // $reflection = new \ReflectionClass(User::class);
        // $property = $reflection->getProperty('lastLoginAt');
        // $attributes = $property->getAttributes(ColumnPermission::class);

        // $this->assertNotEmpty($attributes, 'User lastLoginAt should have ColumnPermission attribute');

        // $permAttr = $attributes[0]->newInstance();

        $attrHelper = new AttributeHelper();
        $attribute = $attrHelper->getPropertyAttribute(
            User::class,
            'lastLoginAt',
            ColumnPermission::class
        );

        $this->assertNotNull($attribute);
        $this->assertEquals(
            'ROLE_ADMIN',
            $attribute->getPermission(AdminEntityVoter::ADMIN_SHOW)
        );
    }

    public function testUserHasAccountAgeCustomColumn(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $customColumns = $reflection->getAttributes(AdminCustomColumn::class);

        $this->assertNotEmpty($customColumns, 'User should have at least one AdminCustomColumn');

        $names = array_map(
            fn(\ReflectionAttribute $attr) => $attr->newInstance()->name,
            $customColumns,
        );
        $this->assertContains('accountAge', $names, 'User should have accountAge custom column');
    }

    public function testUserAccountAgeCustomColumnHasTemplate(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $customColumns = $reflection->getAttributes(AdminCustomColumn::class);

        foreach ($customColumns as $attr) {
            $col = $attr->newInstance();
            if ($col->name === 'accountAge') {
                $this->assertStringEndsWith('.html.twig', $col->template);
                $this->assertNotEmpty($col->label);
                return;
            }
        }

        $this->fail('accountAge AdminCustomColumn not found on User');
    }

    // -------------------------------------------------------------------------
    // Bicycle
    // -------------------------------------------------------------------------

    public function testBicycleHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'Bicycle entity should have Admin attribute');

        $adminAttr = $attributes[0]->newInstance();
        $this->assertEquals('Bike', $adminAttr->getLabel());
        $this->assertEquals('pedal_bike', $adminAttr->getIcon());
        $this->assertTrue($adminAttr->isEnableColumnVisibility());
    }

    public function testBicycleHasInlineEditEnabled(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $adminAttr = $attributes[0]->newInstance();
        $this->assertTrue($adminAttr->isEnableInlineEdit(), 'Bicycle should have enableInlineEdit: true');
    }

    public function testBicycleCreatedAtIsNotEditable(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $property = $reflection->getProperty('createdAt');
        $attrs = $property->getAttributes(AdminColumn::class);

        $this->assertNotEmpty($attrs, 'Bicycle createdAt should have AdminColumn attribute');
        $adminCol = $attrs[0]->newInstance();
        $this->assertFalse($adminCol->editable, 'createdAt should be editable: false');
    }

    public function testBicycleBrandAndModelHaveCompositeGroup(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);

        $brandAttrs = $reflection->getProperty('brand')->getAttributes(AdminColumn::class);
        $modelAttrs = $reflection->getProperty('model')->getAttributes(AdminColumn::class);

        $this->assertNotEmpty($brandAttrs, 'brand should have AdminColumn');
        $this->assertNotEmpty($modelAttrs, 'model should have AdminColumn');

        $brandGroup = $brandAttrs[0]->newInstance()->group;
        $modelGroup = $modelAttrs[0]->newInstance()->group;

        $this->assertNotNull($brandGroup, 'brand should be in a group');
        $this->assertNotNull($modelGroup, 'model should be in a group');
        $this->assertSame($brandGroup, $modelGroup, 'brand and model should share the same composite group');
    }

    public function testBicycleHasAdminColumnGroupAttribute(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $groupAttrs = $reflection->getAttributes(AdminColumnGroup::class);

        $this->assertNotEmpty($groupAttrs, 'Bicycle should have at least one AdminColumnGroup');
    }

    public function testBicycleHasDuplicateAdminAction(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $actionAttrs = $reflection->getAttributes(AdminAction::class);

        $this->assertNotEmpty($actionAttrs, 'Bicycle should have at least one AdminAction');

        $names = array_map(
            fn(\ReflectionAttribute $attr) => $attr->newInstance()->name,
            $actionAttrs,
        );
        $this->assertContains('duplicate', $names, 'Bicycle should have a "duplicate" AdminAction');
    }

    public function testBicycleDuplicateActionHasCondition(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        foreach ($reflection->getAttributes(AdminAction::class) as $attr) {
            $action = $attr->newInstance();
            if ($action->name === 'duplicate') {
                $this->assertNotNull($action->icon);
                $this->assertNotNull($action->route);
                return;
            }
        }
        $this->fail('duplicate AdminAction not found on Bicycle');
    }

    public function testBicyclePartsHasColumnFilter(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $property = $reflection->getProperty('parts');
        $attributes = $property->getAttributes(ColumnFilter::class);

        $this->assertNotEmpty($attributes, 'Bicycle parts should have ColumnFilter attribute');
    }

    // -------------------------------------------------------------------------
    // Part
    // -------------------------------------------------------------------------

    public function testPartHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'Part entity should have Admin attribute');

        /** @var Admin $adminAttr */
        $adminAttr = $attributes[0]->newInstance();
        $this->assertNull($adminAttr->getLabel());
        $this->assertEquals('settings', $adminAttr->getIcon());
        $this->assertTrue($adminAttr->isEnableColumnVisibility());
    }

    public function testPartHasArchiveExpression(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $attrs = $reflection->getAttributes(Admin::class);

        $adminAttr = $attrs[0]->newInstance();
        $this->assertNotNull(
            $adminAttr->getArchiveExpression(),
            'Part should have an archiveExpression configured',
        );
        $this->assertStringContainsString('archived', $adminAttr->getArchiveExpression() ?? '');
    }

    public function testPartHasArchivedProperty(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $this->assertTrue(
            $reflection->hasProperty('archived'),
            'Part entity should have an "archived" property',
        );
    }

    public function testPartArchivedPropertyIsBoolean(): void
    {
        $part = new Part();
        $this->assertFalse($part->isArchived(), 'Part should default to not archived');

        $part->setArchived(true);
        $this->assertTrue($part->isArchived());

        $part->setArchived(false);
        $this->assertFalse($part->isArchived());
    }

    // -------------------------------------------------------------------------
    // Entity basic functionality (regression)
    // -------------------------------------------------------------------------

    public function testUserEntityBasicFunctionality(): void
    {
        $user = new User();
        $user->setName('Test User')
            ->setEmail('test@example.com')
            ->setActive(true);

        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertTrue($user->isActive());
    }

    public function testBicycleEntityBasicFunctionality(): void
    {
        $bicycle = new Bicycle();
        $bicycle->setBrand('Test Brand')
            ->setModel('Test Model')
            ->setColor('Red')
            ->setYear(2024);

        $this->assertEquals('Test Brand', $bicycle->getBrand());
        $this->assertEquals('Test Model', $bicycle->getModel());
        $this->assertEquals('Red', $bicycle->getColor());
        $this->assertEquals(2024, $bicycle->getYear());
        $this->assertCount(0, $bicycle->getParts());
    }

    public function testPartEntityBasicFunctionality(): void
    {
        $part = new Part();
        $part->setName('Test Part')
            ->setManufacturer('Test Manufacturer')
            ->setPrice('99.99');

        $this->assertEquals('Test Part', $part->getName());
        $this->assertEquals('Test Manufacturer', $part->getManufacturer());
        $this->assertEquals('99.99', $part->getPrice());
        $this->assertNull($part->getBicycle());
        $this->assertFalse($part->isArchived());
    }

    public function testBicyclePartRelationship(): void
    {
        $bicycle = new Bicycle();
        $bicycle->setBrand('Trek')->setModel('Domane')->setColor('Blue')->setYear(2023);

        $part = new Part();
        $part->setName('Wheel')->setManufacturer('Bontrager')->setPrice('299.99');

        $bicycle->addPart($part);

        $this->assertCount(1, $bicycle->getParts());
        $this->assertTrue($bicycle->getParts()->contains($part));
        $this->assertSame($bicycle, $part->getBicycle());

        $bicycle->removePart($part);
        $this->assertCount(0, $bicycle->getParts());
    }
}
