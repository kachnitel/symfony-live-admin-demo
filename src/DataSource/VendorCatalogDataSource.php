<?php

declare(strict_types=1);

namespace App\DataSource;

use Kachnitel\AdminBundle\DataSource\ColumnMetadata;
use Kachnitel\AdminBundle\DataSource\DataSourceInterface;
use Kachnitel\AdminBundle\DataSource\FilterMetadata;
use Kachnitel\AdminBundle\DataSource\PaginatedResult;

/**
 * Custom DataSource demonstrating external data integration.
 *
 * Displays a mock catalog of bike parts vendors/manufacturers.
 * In a real scenario, this would fetch from an external API.
 */
class VendorCatalogDataSource implements DataSourceInterface
{
    private const CATEGORIES = [
        'Drivetrain',
        'Wheels',
        'Tires',
        'Saddles',
        'Brakes',
        'Handlebars',
        'Frames',
        'Accessories',
    ];

    /**
     * @var array<int, array{id: int, name: string, country: string, category: string, rating: int, lastUpdated: string}>
     */
    private const MOCK_VENDORS = [
        ['id' => 1, 'name' => 'Shimano', 'country' => 'Japan', 'category' => 'Drivetrain', 'rating' => 5, 'lastUpdated' => '2026-01-10 10:00:00'],
        ['id' => 2, 'name' => 'SRAM', 'country' => 'USA', 'category' => 'Drivetrain', 'rating' => 5, 'lastUpdated' => '2026-01-09 14:30:00'],
        ['id' => 3, 'name' => 'Campagnolo', 'country' => 'Italy', 'category' => 'Drivetrain', 'rating' => 5, 'lastUpdated' => '2026-01-08 09:15:00'],
        ['id' => 4, 'name' => 'FSA', 'country' => 'Taiwan', 'category' => 'Handlebars', 'rating' => 4, 'lastUpdated' => '2026-01-07 11:00:00'],
        ['id' => 5, 'name' => 'Fizik', 'country' => 'Italy', 'category' => 'Saddles', 'rating' => 4, 'lastUpdated' => '2026-01-06 16:45:00'],
        ['id' => 6, 'name' => 'Brooks', 'country' => 'UK', 'category' => 'Saddles', 'rating' => 5, 'lastUpdated' => '2026-01-05 08:30:00'],
        ['id' => 7, 'name' => 'Continental', 'country' => 'Germany', 'category' => 'Tires', 'rating' => 5, 'lastUpdated' => '2026-01-10 07:00:00'],
        ['id' => 8, 'name' => 'Vittoria', 'country' => 'Italy', 'category' => 'Tires', 'rating' => 4, 'lastUpdated' => '2026-01-04 13:20:00'],
        ['id' => 9, 'name' => 'Mavic', 'country' => 'France', 'category' => 'Wheels', 'rating' => 4, 'lastUpdated' => '2026-01-03 10:00:00'],
        ['id' => 10, 'name' => 'DT Swiss', 'country' => 'Switzerland', 'category' => 'Wheels', 'rating' => 5, 'lastUpdated' => '2026-01-10 09:00:00'],
        ['id' => 11, 'name' => 'Zipp', 'country' => 'USA', 'category' => 'Wheels', 'rating' => 5, 'lastUpdated' => '2026-01-09 11:30:00'],
        ['id' => 12, 'name' => 'Enve', 'country' => 'USA', 'category' => 'Wheels', 'rating' => 5, 'lastUpdated' => '2026-01-08 15:00:00'],
        ['id' => 13, 'name' => 'Chris King', 'country' => 'USA', 'category' => 'Accessories', 'rating' => 5, 'lastUpdated' => '2026-01-07 09:45:00'],
        ['id' => 14, 'name' => 'Hope', 'country' => 'UK', 'category' => 'Brakes', 'rating' => 5, 'lastUpdated' => '2026-01-06 14:00:00'],
        ['id' => 15, 'name' => 'Magura', 'country' => 'Germany', 'category' => 'Brakes', 'rating' => 4, 'lastUpdated' => '2026-01-05 10:30:00'],
        ['id' => 16, 'name' => 'Specialized', 'country' => 'USA', 'category' => 'Frames', 'rating' => 5, 'lastUpdated' => '2026-01-10 08:00:00'],
        ['id' => 17, 'name' => 'Trek', 'country' => 'USA', 'category' => 'Frames', 'rating' => 5, 'lastUpdated' => '2026-01-09 12:00:00'],
        ['id' => 18, 'name' => 'Pinarello', 'country' => 'Italy', 'category' => 'Frames', 'rating' => 5, 'lastUpdated' => '2026-01-08 10:00:00'],
    ];

    public function getIdentifier(): string
    {
        return 'vendor-catalog';
    }

    public function getLabel(): string
    {
        return 'Vendor Catalog';
    }

    public function getIcon(): ?string
    {
        return 'storefront';
    }

    public function getColumns(): array
    {
        return [
            'name' => ColumnMetadata::create('name', 'Vendor Name', 'string'),
            'country' => ColumnMetadata::create('country', 'Country', 'string'),
            'category' => ColumnMetadata::create('category', 'Category', 'string'),
            'rating' => ColumnMetadata::create('rating', 'Rating', 'integer'),
            'lastUpdated' => ColumnMetadata::create('lastUpdated', 'Last Updated', 'datetime'),
        ];
    }

    public function getFilters(): array
    {
        return [
            'name' => FilterMetadata::text('name', 'Vendor Name', 'Search vendor...', 1),
            'country' => FilterMetadata::text('country', 'Country', 'Filter by country...', 2),
            'category' => FilterMetadata::enum('category', self::CATEGORIES, 'Category', true, true, 3),
            'lastUpdated' => FilterMetadata::dateRange('lastUpdated', 'Updated Between', 4),
        ];
    }

    public function getDefaultSortBy(): string
    {
        return 'name';
    }

    public function getDefaultSortDirection(): string
    {
        return 'ASC';
    }

    public function getDefaultItemsPerPage(): int
    {
        return 10;
    }

    public function query(
        string $search,
        array $filters,
        string $sortBy,
        string $sortDirection,
        int $page,
        int $itemsPerPage
    ): PaginatedResult {
        $items = self::MOCK_VENDORS;

        // Apply global search
        if ($search !== '') {
            $items = array_filter($items, fn(array $item): bool =>
                stripos($item['name'], $search) !== false ||
                stripos($item['country'], $search) !== false ||
                stripos($item['category'], $search) !== false
            );
        }

        // Apply column filters
        foreach ($filters as $column => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            if ($column === 'lastUpdated' && is_string($value)) {
                $range = json_decode($value, true);
                if (is_array($range)) {
                    $items = array_filter($items, function (array $item) use ($range): bool {
                        $date = strtotime($item['lastUpdated']);
                        if ($date === false) {
                            return false;
                        }
                        if (!empty($range['from'])) {
                            $fromTime = strtotime($range['from']);
                            if ($fromTime !== false && $date < $fromTime) {
                                return false;
                            }
                        }
                        if (!empty($range['to'])) {
                            $toTime = strtotime($range['to'] . ' 23:59:59');
                            if ($toTime !== false && $date > $toTime) {
                                return false;
                            }
                        }
                        return true;
                    });
                }
            } elseif ($column === 'category') {
                // Multi-select enum filter: value arrives as JSON string from EnumMultiFilter
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                if (is_array($decoded)) {
                    $items = array_filter($items, fn(array $item): bool =>
                        in_array($item[$column], $decoded, true)
                    );
                } else {
                    $items = array_filter($items, fn(array $item): bool =>
                        $item[$column] === $value
                    );
                }
            } else {
                // Partial match for text filters
                $items = array_filter($items, fn(array $item): bool =>
                    stripos((string) ($item[$column] ?? ''), (string) $value) !== false
                );
            }
        }

        // Sort
        usort($items, function (array $a, array $b) use ($sortBy, $sortDirection): int {
            $aVal = $a[$sortBy] ?? '';
            $bVal = $b[$sortBy] ?? '';
            $cmp = $aVal <=> $bVal;
            return $sortDirection === 'DESC' ? -$cmp : $cmp;
        });

        // Paginate
        $total = count($items);
        $offset = ($page - 1) * $itemsPerPage;
        $items = array_slice($items, $offset, $itemsPerPage);

        // Convert to objects
        $objects = array_map(fn(array $item): object => (object) $item, $items);

        return new PaginatedResult($objects, $total, $page, $itemsPerPage);
    }

    public function find(string|int $id): ?object
    {
        foreach (self::MOCK_VENDORS as $item) {
            if ($item['id'] === (int) $id) {
                return (object) $item;
            }
        }
        return null;
    }

    public function supportsAction(string $action): bool
    {
        return in_array($action, ['index', 'show'], true);
    }

    public function getIdField(): string
    {
        return 'id';
    }

    public function getItemId(object $item): string|int
    {
        return $item->id;
    }

    public function getItemValue(object $item, string $field): mixed
    {
        return $item->$field ?? null;
    }
}
