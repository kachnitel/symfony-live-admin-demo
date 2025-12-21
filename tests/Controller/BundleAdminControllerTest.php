<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BundleAdminControllerTest extends WebTestCase
{
    private static ?User $testUser = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize test database with sample data
        self::bootKernel();
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Drop and recreate schema to ensure clean state
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Load demo data
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::$kernel);
        $application->setAutoExit(false);

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'app:load-demo-data',
        ]);
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $application->run($input, $output);

        // Create a test user for authentication
        $passwordHasher = $container->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');
        self::$testUser = new User();
        self::$testUser->setEmail('test-admin@example.com');
        self::$testUser->setName('Test Admin');
        self::$testUser->setActive(true);
        self::$testUser->setPassword($passwordHasher->hashPassword(self::$testUser, 'testpass'));
        $entityManager->persist(self::$testUser);
        $entityManager->flush();

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

        self::$testUser = null;
        self::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }

    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();

        // Refetch user from database
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test-admin@example.com']);
        $client->loginUser($user);

        return $client;
    }

    public function testBundleDashboardLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testBundleUserIndexPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
    }

    public function testBundleBicycleIndexPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/bicycle');

        $this->assertResponseIsSuccessful();
    }

    public function testBundlePartIndexPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/part');

        $this->assertResponseIsSuccessful();
    }

    public function testBundleInvalidEntityReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/nonexistent');

        $this->assertResponseStatusCodeSame(404);
    }
}
