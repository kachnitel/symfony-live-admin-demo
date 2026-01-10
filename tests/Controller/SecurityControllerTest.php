<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize test database
        self::bootKernel();
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Drop and recreate schema to ensure clean state
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

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

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Log in');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');

        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Test User');
        $user->setActive(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setPassword($passwordHasher->hashPassword($user, 'testpassword'));

        $entityManager->persist($user);
        $entityManager->flush();
        $userId = $user->getId();

        // Submit login form
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Log in')->form([
            'email' => 'test@example.com',
            'password' => 'testpassword',
        ]);

        $client->submit($form);

        // Should redirect to admin index after successful login
        $this->assertResponseRedirects('/admin');

        // Clean up - refetch the entity
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = $entityManager->find(User::class, $userId);
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    public function testAdminRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        // Should redirect to login page when not authenticated
        $this->assertResponseRedirects('/login');
    }

    public function testAdminAccessibleWhenAuthenticated(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');

        // Create and authenticate user
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setName('Admin User');
        $user->setActive(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setPassword($passwordHasher->hashPassword($user, 'adminpass'));

        $entityManager->persist($user);
        $entityManager->flush();

        // Login
        $client->loginUser($user);

        // Access admin page
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();

        // Clean up
        $entityManager->remove($user);
        $entityManager->flush();
    }

    public function testUserEntityRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/user');

        // Should redirect to login page when not authenticated
        $this->assertResponseRedirects('/login');
    }
}
