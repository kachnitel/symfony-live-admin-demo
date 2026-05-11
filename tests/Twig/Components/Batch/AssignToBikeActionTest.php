<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components\Batch;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use App\Twig\Components\Batch\AssignToBikeAction;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * @group batch-actions
 */
#[Group('batch-actions')]
class AssignToBikeActionTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    private static ?User $testUser = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::bootKernel();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
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
        self::$testUser->setEmail('assign-bike-test@example.com');
        self::$testUser->setName('Assign Bike Test User');
        self::$testUser->setActive(true);
        self::$testUser->setCreatedAt(new \DateTimeImmutable());
        self::$testUser->setPassword($passwordHasher->hashPassword(self::$testUser, 'testpass'));
        $em->persist(self::$testUser);
        $em->flush();

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema($em->getMetadataFactory()->getAllMetadata());
        self::$testUser = null;
        self::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }

    private function getTestUser(): User
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'assign-bike-test@example.com']);
        return $user;
    }

    public function testComponentRendersWithEmptySelection(): void
    {
        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => [],
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
        ])->actingAs($this->getTestUser());

        $rendered = $component->render();
        $this->assertStringContainsString('Select parts', $rendered->toString());
    }

    public function testComponentRendersWithSelectedParts(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $parts = $em->getRepository(Part::class)->findBy([], null, 2);

        $ids = array_map(fn (Part $p) => $p->getId(), $parts);

        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => $ids,
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
        ])->actingAs($this->getTestUser());

        $rendered = $component->render();
        $html = $rendered->toString();

        $this->assertStringContainsString('Select a bicycle', $html);
        $this->assertStringContainsString('Assign', $html);
    }

    public function testComponentListsBicycles(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $bicycles = $em->getRepository(Bicycle::class)->findBy([], null, 3);
        $parts = $em->getRepository(Part::class)->findBy([], null, 1);

        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => [$parts[0]->getId()],
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
        ])->actingAs($this->getTestUser());

        $html = $component->render()->toString();

        // At least one bicycle should be listed in the select
        $this->assertStringContainsString($bicycles[0]->getBrand(), $html);
    }

    public function testExecuteAssignsPartsToSelectedBicycle(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        // Find standalone parts (no bicycle)
        $standaloneParts = $em->getRepository(Part::class)
            ->createQueryBuilder('p')
            ->where('p.bicycle IS NULL')
            ->andWhere('p.archived = false')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($standaloneParts, 'Need standalone parts for this test');

        $bicycle = $em->getRepository(Bicycle::class)->findOneBy([]);
        $this->assertNotNull($bicycle, 'Need at least one bicycle');

        $partIds = array_map(fn (Part $p) => $p->getId(), $standaloneParts);

        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => $partIds,
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
            'bicycleId'        => $bicycle->getId(),
        ])->actingAs($this->getTestUser());

        $component->call('execute');

        // Verify parts were assigned
        $em->clear();
        foreach ($partIds as $id) {
            $part = $em->find(Part::class, $id);
            $this->assertNotNull($part?->getBicycle(), "Part $id should now be assigned to a bicycle");
            $this->assertSame($bicycle->getId(), $part->getBicycle()->getId());
        }
    }

    public function testExecuteDoesNothingWithNoBicycleSelected(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $parts = $em->getRepository(Part::class)->findBy([], null, 1);

        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => [$parts[0]->getId()],
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
            'bicycleId'        => null,
        ])->actingAs($this->getTestUser());

        // Should not throw
        $component->call('execute');
        $this->assertTrue(true);
    }

    public function testExecuteDoesNothingWithEmptySelection(): void
    {
        $component = $this->createLiveComponent('App:Batch:AssignToBike', [
            'selectedIds'      => [],
            'entityClass'      => Part::class,
            'entityShortClass' => 'Part',
            'bicycleId'        => 1,
        ])->actingAs($this->getTestUser());

        // Should not throw
        $component->call('execute');
        $this->assertTrue(true);
    }
}
