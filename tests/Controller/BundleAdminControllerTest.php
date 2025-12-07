<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BundleAdminControllerTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize test database with sample data
        self::bootKernel();
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

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test database
        self::bootKernel();
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);

        self::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }

    public function testBundleDashboardLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testBundleUserIndexPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
    }

    public function testBundleBicycleIndexPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/bicycle');

        $this->assertResponseIsSuccessful();
    }

    public function testBundlePartIndexPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/part');

        $this->assertResponseIsSuccessful();
    }

    public function testBundleInvalidEntityReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/nonexistent');

        $this->assertResponseStatusCodeSame(404);
    }
}
