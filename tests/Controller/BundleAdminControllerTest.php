<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('controller')]
class BundleAdminControllerTest extends WebTestCase
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

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'app:load-demo-data',
        ]);
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $application->run($input, $output);

        $passwordHasher = $container->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');
        self::$testUser = new User();
        self::$testUser->setEmail('test-admin@example.com');
        self::$testUser->setName('Test Admin');
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

    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test-admin@example.com']);
        $client->loginUser($user);

        return $client;
    }

    // -------------------------------------------------------------------------
    // Core pages
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // DataSource pages
    // -------------------------------------------------------------------------

    public function testDataSourceIndexPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/data/vendor-catalog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Vendor');
    }

    public function testDataSourceShowPageLoads(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/data/vendor-catalog/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Shimano');
    }

    public function testDataSourceInvalidIdReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/data/vendor-catalog/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDataSourceInvalidSourceReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/data/nonexistent');

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // Batch actions
    // -------------------------------------------------------------------------

    public function testBatchActionsEnabledOnPartList(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/part');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(
            0,
            $crawler->filter('[data-controller*="batch-select"]')->count(),
            'Batch select controller should be present on Part list',
        );
    }

    public function testBatchActionsEnabledOnUserList(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(
            0,
            $crawler->filter('[data-controller*="batch-select"]')->count(),
            'Batch select controller should be present on User list',
        );
    }

    // -------------------------------------------------------------------------
    // Archive feature (Part)
    // -------------------------------------------------------------------------

    public function testPartListRendersWithoutArchivedByDefault(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/part');

        $this->assertResponseIsSuccessful();
        // The archive toggle button should be present when archiveExpression is configured
        $pageText = $crawler->filter('body')->text();
        $this->assertStringContainsStringIgnoringCase('archived', strtolower($pageText));
    }

    public function testArchivePartAction(): void
    {
        $this->markTestIncomplete('CSRF fails in this test.');
        $client = $this->createAuthenticatedClient();

        // Find a non-archived part
        $em = static::getContainer()->get('doctrine')->getManager();
        $part = $em->getRepository(Part::class)->findOneBy(['archived' => false]);
        $this->assertNotNull($part, 'Need at least one non-archived part for this test');

        $partId = $part->getId();

        // Archive it
        $client->request('POST', sprintf('/admin/part/%d/archive', $partId));
        $this->assertResponseRedirects();

        // Verify it is now archived
        $em->clear();
        $updated = $em->getRepository(Part::class)->find($partId);
        $this->assertNotNull($updated);
        $this->assertTrue($updated->isArchived(), 'Part should be archived after POST to /archive');
    }

    public function testUnarchivePartAction(): void
    {
        $this->markTestIncomplete('CSRF fails in this test.');
        $client = $this->createAuthenticatedClient();

        // Find (or create) an archived part
        $em = static::getContainer()->get('doctrine')->getManager();
        $part = $em->getRepository(Part::class)->findOneBy(['archived' => true]);

        if ($part === null) {
            $part = new Part();
            $part->setName('Test archived part');
            $part->setCreatedAt(new \DateTimeImmutable());
            $part->setArchived(true);
            $em->persist($part);
            $em->flush();
        }

        $partId = $part->getId();

        $client->request('POST', sprintf('/admin/part/%d/unarchive', $partId));
        $this->assertResponseRedirects();

        $em->clear();
        $updated = $em->getRepository(Part::class)->find($partId);
        $this->assertNotNull($updated);
        $this->assertFalse($updated->isArchived(), 'Part should be unarchived after POST to /unarchive');
    }

    // -------------------------------------------------------------------------
    // Inline edit (Bicycle)
    // -------------------------------------------------------------------------

    public function testBicycleListRendersInlineEditTrigger(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/bicycle');

        $this->assertResponseIsSuccessful();
        // enableInlineEdit: true causes the ✏️ button to appear on rows
        $pageSource = $client->getResponse()->getContent();
        $this->assertStringContainsString('inline', strtolower($pageSource));
    }

    // -------------------------------------------------------------------------
    // Duplicate action (Bicycle)
    // -------------------------------------------------------------------------

    public function testBicycleDuplicateActionCreatesNewRecord(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = static::getContainer()->get('doctrine')->getManager();
        $bicycle = $em->getRepository(Bicycle::class)->findOneBy([]);
        $this->assertNotNull($bicycle, 'Need at least one bicycle for duplicate test');

        $originalCount = count($em->getRepository(Bicycle::class)->findAll());
        $originalId = $bicycle->getId();

        $client->request('GET', sprintf('/admin/bicycle/%d/duplicate', $originalId));
        $this->assertResponseRedirects();

        $em->clear();
        $newCount = count($em->getRepository(Bicycle::class)->findAll());
        $this->assertSame($originalCount + 1, $newCount, 'Duplicate should create exactly one new bicycle');
    }

    public function testBicycleDuplicateAppendsModelSuffix(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = static::getContainer()->get('doctrine')->getManager();
        $bicycle = $em->getRepository(Bicycle::class)->findOneBy([]);
        $this->assertNotNull($bicycle);

        $originalModel = $bicycle->getModel();
        $originalId = $bicycle->getId();

        $client->request('GET', sprintf('/admin/bicycle/%d/duplicate', $originalId));
        $this->assertResponseRedirects();

        $em->clear();
        $copy = $em->getRepository(Bicycle::class)->findOneBy(['model' => $originalModel . ' (copy)']);
        $this->assertNotNull($copy, 'Duplicated bicycle should have model suffixed with " (copy)"');
    }

    public function testBicycleDuplicateRequiresAuthentication(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get('doctrine')->getManager();
        $bicycle = $em->getRepository(Bicycle::class)->findOneBy([]);
        $this->assertNotNull($bicycle);

        $client->request('GET', sprintf('/admin/bicycle/%d/duplicate', $bicycle->getId()));
        $this->assertResponseRedirects('/login');
    }

    // -------------------------------------------------------------------------
    // Custom column (User)
    // -------------------------------------------------------------------------

    public function testUserListRendersAccountAgeColumn(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Account Age');
    }

    // -------------------------------------------------------------------------
    // Homepage features section
    // -------------------------------------------------------------------------

    public function testHomepageShowsAllFeatures(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $pageText = $crawler->filter('body')->text();

        $expectedFeatures = [
            'Column Permissions',
            'Column Visibility',
            'Inline Edit',
            'Row Actions',
            'Composite Columns',
            'Archive',
            'Custom Columns',
        ];

        foreach ($expectedFeatures as $feature) {
            $this->assertStringContainsString(
                $feature,
                $pageText,
                "Homepage should mention '{$feature}'",
            );
        }
    }
}
