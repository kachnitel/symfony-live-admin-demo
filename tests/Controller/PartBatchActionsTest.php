<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group batch-actions
 */
#[Group('batch-actions')]
class PartBatchActionsTest extends WebTestCase
{
    private static ?User $testUser = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::bootKernel();
        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::$kernel);
        $application->setAutoExit(false);
        $application->run(
            new \Symfony\Component\Console\Input\ArrayInput(['command' => 'app:load-demo-data']),
            new \Symfony\Component\Console\Output\NullOutput()
        );

        $passwordHasher = $container->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');
        self::$testUser = new User();
        self::$testUser->setEmail('batch-test@example.com');
        self::$testUser->setName('Batch Test User');
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
        $schemaTool->dropSchema($entityManager->getMetadataFactory()->getAllMetadata());
        self::$testUser = null;
        self::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }

    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'batch-test@example.com']);
        $client->loginUser($user);
        return $client;
    }

    public function testBatchDetachRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/part/batch-detach', ['ids' => [1]]);

        $this->assertResponseRedirects('/login');
    }

    public function testBatchDetachWithNoIdsRedirectsWithWarning(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/admin/part/batch-detach', ['ids' => []]);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'No parts selected');
    }

    public function testBatchDetachDetachesPartsFromBicycle(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Find parts that are attached to a bicycle
        $attachedParts = $em->getRepository(Part::class)
            ->createQueryBuilder('p')
            ->where('p.bicycle IS NOT NULL')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($attachedParts, 'Demo data should have parts attached to bicycles');

        $ids = array_map(fn (Part $p) => $p->getId(), $attachedParts);

        $client->request('POST', '/admin/part/batch-detach', ['ids' => $ids]);

        $this->assertResponseRedirects();

        // Re-fetch to check the parts are detached
        $em->clear();
        foreach ($ids as $id) {
            $part = $em->find(Part::class, $id);
            $this->assertNull($part?->getBicycle(), "Part $id should have no bicycle after detach");
        }
    }

    public function testBatchDetachIgnoresPartsAlreadyWithoutBicycle(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Find standalone parts (not attached to a bicycle)
        $standaloneParts = $em->getRepository(Part::class)
            ->createQueryBuilder('p')
            ->where('p.bicycle IS NULL')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($standaloneParts, 'Demo data should have standalone parts');

        $ids = array_map(fn (Part $p) => $p->getId(), $standaloneParts);

        $client->request('POST', '/admin/part/batch-detach', ['ids' => $ids]);

        // Should still succeed gracefully — count = 0 means "0 part(s) detached"
        $this->assertResponseRedirects();
    }

    public function testBatchDetachOnlyAcceptsPostMethod(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/part/batch-detach');

        $this->assertResponseStatusCodeSame(405);
    }

    public function testPartListPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/part');

        $this->assertResponseIsSuccessful();
    }
}
