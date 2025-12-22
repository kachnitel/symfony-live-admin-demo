<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Browser tests for the batch-select Stimulus controller.
 *
 * These tests require a real browser and are excluded from normal test runs.
 * Run with: vendor/bin/phpunit tests/Browser/
 */
#[Group('browser')]
class BatchSelectControllerTest extends PantherTestCase
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
        self::$testUser->setEmail('browser-test@example.com');
        self::$testUser->setName('Browser Test User');
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

    private function loginAndNavigateToList(): \Symfony\Component\Panther\Client
    {
        $client = static::createPantherClient();

        // Login
        $client->request('GET', '/login');
        $client->submitForm('Log in', [
            'email' => 'browser-test@example.com',
            'password' => 'testpass',
        ]);

        // Navigate to Part list page (has batch actions enabled)
        $client->request('GET', '/admin/part');

        // Wait briefly for JS to initialize
        usleep(500000); // 500ms

        return $client;
    }

    public function testPageLoadsWithBatchSelect(): void
    {
        $client = static::createPantherClient();

        // Login
        $client->request('GET', '/login');
        $client->submitForm('Log in', [
            'email' => 'browser-test@example.com',
            'password' => 'testpass',
        ]);

        // Navigate to Part list page
        $crawler = $client->request('GET', '/admin/part');

        // Debug: output page title and URL
        $currentUrl = $client->getCurrentURL();
        $pageSource = $client->getPageSource();

        $this->assertStringContainsString('/admin/part', $currentUrl, "Should be on /admin/part, got: $currentUrl");
        $this->assertStringContainsString('data-controller', $pageSource, 'Page should contain data-controller');
    }

    public function testMasterCheckboxSelectsAll(): void
    {
        $client = $this->loginAndNavigateToList();

        // Verify we have checkboxes to test
        $checkboxCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]').length"
        );
        $this->assertGreaterThan(0, $checkboxCount, 'Should have row checkboxes');

        // Initially none should be checked
        $checkedCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]:checked').length"
        );
        $this->assertEquals(0, $checkedCount, 'Initially no checkboxes should be checked');

        // Click master checkbox to select all
        $client->executeScript(
            "document.querySelector('[data-batch-select-target=\"master\"]').click()"
        );
        usleep(200000); // 200ms for JS to execute

        // All row checkboxes should now be checked
        $checkedCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]:checked').length"
        );
        $this->assertEquals($checkboxCount, $checkedCount, 'All checkboxes should be checked after clicking master');
    }

    public function testMasterCheckboxDeselectsAll(): void
    {
        $client = $this->loginAndNavigateToList();

        // Click master to select all
        $client->executeScript(
            "document.querySelector('[data-batch-select-target=\"master\"]').click()"
        );
        usleep(200000);

        // Click master again to deselect all
        $client->executeScript(
            "document.querySelector('[data-batch-select-target=\"master\"]').click()"
        );
        usleep(200000);

        // Verify all unchecked
        $checkedCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]:checked').length"
        );
        $this->assertEquals(0, $checkedCount, 'All checkboxes should be unchecked after clicking master twice');
    }

    public function testIndividualCheckboxToggle(): void
    {
        $client = $this->loginAndNavigateToList();

        $checkboxCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]').length"
        );
        $this->assertGreaterThan(1, $checkboxCount, 'Need at least 2 checkboxes for this test');

        // Click first checkbox
        $client->executeScript(
            "document.querySelectorAll('[data-batch-select-target=\"checkbox\"]')[0].click()"
        );
        usleep(200000);

        // Verify only first is checked
        $checkedCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]:checked').length"
        );
        $this->assertEquals(1, $checkedCount, 'Only one checkbox should be checked');

        // Verify master is in indeterminate state (some checked)
        $isIndeterminate = $client->executeScript(
            "return document.querySelector('[data-batch-select-target=\"master\"]').indeterminate"
        );
        $this->assertTrue($isIndeterminate, 'Master checkbox should be indeterminate when some are checked');
    }

    public function testShiftClickRangeSelection(): void
    {
        $client = $this->loginAndNavigateToList();

        $checkboxCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]').length"
        );
        $this->assertGreaterThanOrEqual(3, $checkboxCount, 'Need at least 3 checkboxes for range test');

        // Click first checkbox normally
        $client->executeScript(
            "document.querySelectorAll('[data-batch-select-target=\"checkbox\"]')[0].click()"
        );
        usleep(200000);

        // Shift+click on third checkbox for range selection
        $client->executeScript("
            const checkboxes = document.querySelectorAll('[data-batch-select-target=\"checkbox\"]');
            const thirdCheckbox = checkboxes[2];
            const event = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                shiftKey: true
            });
            thirdCheckbox.dispatchEvent(event);
        ");
        usleep(200000);

        // Verify checkboxes 0, 1, 2 are all checked
        for ($i = 0; $i < 3; $i++) {
            $isChecked = $client->executeScript(
                "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]')[$i].checked"
            );
            $this->assertTrue($isChecked, "Checkbox at index $i should be checked after shift+click range selection");
        }
    }

    public function testMasterCheckboxStateAfterAllManuallySelected(): void
    {
        $client = $this->loginAndNavigateToList();

        $checkboxCount = $client->executeScript(
            "return document.querySelectorAll('[data-batch-select-target=\"checkbox\"]').length"
        );
        $this->assertGreaterThan(0, $checkboxCount, 'Need checkboxes for this test');

        // Click all checkboxes individually
        for ($i = 0; $i < $checkboxCount; $i++) {
            $client->executeScript(
                "document.querySelectorAll('[data-batch-select-target=\"checkbox\"]')[$i].click()"
            );
            usleep(100000);
        }

        usleep(200000);

        // Master should be checked (not indeterminate) when all are selected
        $masterChecked = $client->executeScript(
            "return document.querySelector('[data-batch-select-target=\"master\"]').checked"
        );
        $masterIndeterminate = $client->executeScript(
            "return document.querySelector('[data-batch-select-target=\"master\"]').indeterminate"
        );

        $this->assertTrue($masterChecked, 'Master should be checked when all rows are selected');
        $this->assertFalse($masterIndeterminate, 'Master should not be indeterminate when all rows are selected');
    }
}
