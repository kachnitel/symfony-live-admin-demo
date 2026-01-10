<?php

declare(strict_types=1);

namespace App\Tests\DataSource;

use App\DataSource\VendorCatalogDataSource;
use PHPUnit\Framework\TestCase;

class VendorCatalogDataSourceTest extends TestCase
{
    private VendorCatalogDataSource $dataSource;

    protected function setUp(): void
    {
        $this->dataSource = new VendorCatalogDataSource();
    }

    public function testGetIdentifier(): void
    {
        $this->assertSame('vendor-catalog', $this->dataSource->getIdentifier());
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Vendor Catalog', $this->dataSource->getLabel());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('storefront', $this->dataSource->getIcon());
    }

    public function testGetColumns(): void
    {
        $columns = $this->dataSource->getColumns();

        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('country', $columns);
        $this->assertArrayHasKey('category', $columns);
        $this->assertArrayHasKey('rating', $columns);
        $this->assertArrayHasKey('lastUpdated', $columns);
    }

    public function testGetFilters(): void
    {
        $filters = $this->dataSource->getFilters();

        $this->assertArrayHasKey('name', $filters);
        $this->assertArrayHasKey('country', $filters);
        $this->assertArrayHasKey('category', $filters);
        $this->assertArrayHasKey('lastUpdated', $filters);
    }

    public function testQueryReturnsResults(): void
    {
        $result = $this->dataSource->query('', [], 'name', 'ASC', 1, 10);

        $this->assertGreaterThan(0, $result->totalItems);
        $this->assertLessThanOrEqual(10, count($result->items));
        $this->assertSame(1, $result->currentPage);
    }

    public function testQueryWithSearch(): void
    {
        $result = $this->dataSource->query('Shimano', [], 'name', 'ASC', 1, 10);

        $this->assertGreaterThan(0, $result->totalItems);
        foreach ($result->items as $item) {
            $this->assertStringContainsStringIgnoringCase('Shimano', $item->name);
        }
    }

    public function testQueryWithCountrySearch(): void
    {
        $result = $this->dataSource->query('Japan', [], 'name', 'ASC', 1, 10);

        $this->assertGreaterThan(0, $result->totalItems);
    }

    public function testQueryWithCategoryFilter(): void
    {
        $result = $this->dataSource->query('', ['category' => 'Drivetrain'], 'name', 'ASC', 1, 10);

        $this->assertGreaterThan(0, $result->totalItems);
        foreach ($result->items as $item) {
            $this->assertSame('Drivetrain', $item->category);
        }
    }

    public function testQueryWithTextFilter(): void
    {
        $result = $this->dataSource->query('', ['country' => 'USA'], 'name', 'ASC', 1, 10);

        $this->assertGreaterThan(0, $result->totalItems);
        foreach ($result->items as $item) {
            $this->assertStringContainsStringIgnoringCase('USA', $item->country);
        }
    }

    public function testQueryWithDateRangeFilter(): void
    {
        $filter = json_encode(['from' => '2026-01-09', 'to' => '2026-01-10']);
        $result = $this->dataSource->query('', ['lastUpdated' => $filter], 'name', 'ASC', 1, 20);

        $this->assertGreaterThan(0, $result->totalItems);
    }

    public function testQuerySorting(): void
    {
        $resultAsc = $this->dataSource->query('', [], 'name', 'ASC', 1, 20);
        $resultDesc = $this->dataSource->query('', [], 'name', 'DESC', 1, 20);

        $this->assertNotEmpty($resultAsc->items);
        $this->assertNotEmpty($resultDesc->items);

        // First item in ASC should be different from first item in DESC (unless there's only one)
        if (count($resultAsc->items) > 1) {
            $this->assertNotSame($resultAsc->items[0]->name, $resultDesc->items[0]->name);
        }
    }

    public function testQueryPagination(): void
    {
        $page1 = $this->dataSource->query('', [], 'name', 'ASC', 1, 5);
        $page2 = $this->dataSource->query('', [], 'name', 'ASC', 2, 5);

        $this->assertSame(1, $page1->currentPage);
        $this->assertSame(2, $page2->currentPage);
        $this->assertSame(5, $page1->itemsPerPage);

        if ($page1->totalItems > 5) {
            $this->assertNotEmpty($page2->items);
            // Ensure pages contain different items
            $this->assertNotSame($page1->items[0]->id, $page2->items[0]->id);
        }
    }

    public function testFindReturnsItem(): void
    {
        $item = $this->dataSource->find(1);

        $this->assertNotNull($item);
        $this->assertSame(1, $item->id);
        $this->assertSame('Shimano', $item->name);
    }

    public function testFindReturnsNullForInvalidId(): void
    {
        $item = $this->dataSource->find(999);

        $this->assertNull($item);
    }

    public function testSupportsAction(): void
    {
        $this->assertTrue($this->dataSource->supportsAction('index'));
        $this->assertTrue($this->dataSource->supportsAction('show'));
        $this->assertFalse($this->dataSource->supportsAction('create'));
        $this->assertFalse($this->dataSource->supportsAction('edit'));
        $this->assertFalse($this->dataSource->supportsAction('delete'));
        $this->assertFalse($this->dataSource->supportsAction('batch_delete'));
    }

    public function testGetIdField(): void
    {
        $this->assertSame('id', $this->dataSource->getIdField());
    }

    public function testGetItemId(): void
    {
        $item = $this->dataSource->find(1);
        $this->assertNotNull($item);

        $this->assertSame(1, $this->dataSource->getItemId($item));
    }

    public function testGetItemValue(): void
    {
        $item = $this->dataSource->find(1);
        $this->assertNotNull($item);

        $this->assertSame('Shimano', $this->dataSource->getItemValue($item, 'name'));
        $this->assertSame('Japan', $this->dataSource->getItemValue($item, 'country'));
        $this->assertSame('Drivetrain', $this->dataSource->getItemValue($item, 'category'));
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('name', $this->dataSource->getDefaultSortBy());
        $this->assertSame('ASC', $this->dataSource->getDefaultSortDirection());
        $this->assertSame(10, $this->dataSource->getDefaultItemsPerPage());
    }
}
