<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use Kachnitel\AdminBundle\Attribute\Admin;
use PHPUnit\Framework\TestCase;

class EntityAttributeTest extends TestCase
{
    public function testUserHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'User entity should have Admin attribute');

        $adminAttr = $attributes[0]->newInstance();
        $this->assertEquals('person', $adminAttr->getIcon());
    }

    public function testBicycleHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(Bicycle::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'Bicycle entity should have Admin attribute');

        $adminAttr = $attributes[0]->newInstance();
        $this->assertEquals('Bike', $adminAttr->getLabel());
        $this->assertEquals('pedal_bike', $adminAttr->getIcon());
    }

    public function testPartHasAdminAttribute(): void
    {
        $reflection = new \ReflectionClass(Part::class);
        $attributes = $reflection->getAttributes(Admin::class);

        $this->assertNotEmpty($attributes, 'Part entity should have Admin attribute');

        /** @var Admin $adminAttr */
        $adminAttr = $attributes[0]->newInstance();
        $this->assertEquals(null, $adminAttr->getLabel());
        $this->assertEquals('settings', $adminAttr->getIcon());
    }

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
