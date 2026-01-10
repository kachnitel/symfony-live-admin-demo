<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class AdminControllerTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    private static ?User $testUser = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
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
        self::$testUser->setEmail('test-component@example.com');
        self::$testUser->setName('Test Component User');
        self::$testUser->setActive(true);
        self::$testUser->setCreatedAt(new \DateTimeImmutable());
        self::$testUser->setPassword($passwordHasher->hashPassword(self::$testUser, 'testpass'));
        $entityManager->persist(self::$testUser);
        $entityManager->flush();

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
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

    private function getTestUser(): User
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        return $entityManager->getRepository(User::class)->findOneBy(['email' => 'test-component@example.com']);
    }

    public function testEntityListComponentRendersForUser(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => User::class,
            'entityShortClass' => 'User',
        ])->actingAs($this->getTestUser());

        $rendered = $component->render();
        $this->assertStringContainsString('User', $rendered->toString());
    }

    public function testEntityListComponentRendersForBicycle(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => \App\Entity\Bicycle::class,
            'entityShortClass' => 'Bicycle',
        ])->actingAs($this->getTestUser());

        $rendered = $component->render();
        $this->assertStringContainsString('Bicycle', $rendered->toString());
    }

    public function testEntityListComponentRendersForPart(): void
    {
        $component = $this->createLiveComponent('K:Admin:EntityList', [
            'entityClass' => \App\Entity\Part::class,
            'entityShortClass' => 'Part',
        ])->actingAs($this->getTestUser());

        $rendered = $component->render();
        $this->assertStringContainsString('Part', $rendered->toString());
    }
}
