<?php

declare(strict_types=1);

namespace App\Tests\DataSource;

use App\DataSource\VendorCatalogDataSource;
use Kachnitel\DataSourceContracts\DataSourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group datasource-contracts
 */
class VendorCatalogNamespaceTest extends TestCase
{
    public function testVendorCatalogImplementsContractsInterface(): void
    {
        $dataSource = new VendorCatalogDataSource();

        // After migration, must implement the contracts package interface
        $this->assertInstanceOf(DataSourceInterface::class, $dataSource);
    }
}
