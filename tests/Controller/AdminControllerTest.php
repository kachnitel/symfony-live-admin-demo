<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class AdminControllerTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        // Initialize test database with sample data
        $this->initializeTestDatabase();
    }

    protected function tearDown(): void
    {
        // Clean up test database
        $this->cleanupTestDatabase();
        parent::tearDown();
    }

    private function initializeTestDatabase(): void
    {
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create schema
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // Load demo data
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::$kernel);
        $application->setAutoExit(false);

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'app:load-demo-data',
        ]);
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $application->run($input, $output);
    }

    private function cleanupTestDatabase(): void
    {
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }

    public function testEntityListComponentRendersForUser(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => \App\Entity\User::class,
            'entityShortClass' => 'User',
        ])->actingAs(new InMemoryUser(
            'testUser',
            null,
            ['ROLE_ADMIN']
        ));

        $rendered = $component->render();
        $this->assertStringContainsString('User', $rendered->toString());
    }

    public function testEntityListComponentRendersForBicycle(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => \App\Entity\Bicycle::class,
            'entityShortClass' => 'Bicycle',
        ]);

        $rendered = $component->render();
        $this->assertStringContainsString('Bicycle', $rendered->toString());
    }

    public function testEntityListComponentRendersForPart(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => \App\Entity\Part::class,
            'entityShortClass' => 'Part',
        ]);

        $rendered = $component->render();
        $this->assertStringContainsString('Part', $rendered->toString());
    }
}
