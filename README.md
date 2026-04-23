# Admin Bundle Demo

A minimal demonstration of **kachnitel/admin-bundle** showcasing LiveComponents for entity management.

## Quick Start

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:load-demo-data
php bin/console app:create-demo-user
symfony server:start
```

**Login:** `user@example.com` / `password`

## Bundle Version

Tracks **dev-master** of `kachnitel/admin-bundle` (currently v0.9.x).

| Package | Version | Notes |
|---|---|---|
| `kachnitel/admin-bundle` | `dev-master` | Core bundle |
| `kachnitel/datasource-contracts` | `dev-master` | Extracted in v0.9 — `DataSourceInterface` etc. |
| `symfony/ux-autocomplete` | `^2.32` | Required for relation filters |

---

## Demo Features

### Two Implementation Approaches

#### Bundle's GenericAdminController
Auto-discovery via `#[Admin]` attribute — zero controller code needed.
- `/admin` — Dashboard
- `/admin/user`, `/admin/bicycle`, `/admin/part` — Auto-generated CRUD

#### Custom Controller (`AdminController`)
Single generic template at `templates/admin/entity.html.twig` handles all entities via route parameter.
- `/custom-admin/user`, `/custom-admin/bicycle`, `/custom-admin/part`

### Entity Configuration

```php
#[Admin(icon: 'person', enableColumnVisibility: true)]
class User { }

#[Admin(label: 'Bike', icon: 'pedal_bike', enableBatchActions: true)]
class Bicycle { }

// Archive / soft-delete (v0.9)
#[Admin(icon: 'settings', enableBatchActions: true, archiveExpression: 'item.archived')]
class Part {
    private bool $archived = false;
}
```

### Archive / Soft-Delete (v0.9)

`Part` demonstrates the archive feature. The Parts list hides archived rows by default with a live toggle to reveal them. ~20% of standalone parts are seeded as archived by `app:load-demo-data`.

### Template Overrides

| Template | Effect |
|---|---|
| `types/boolean/_preview.html.twig` | Colored Yes/No badges for all boolean fields |
| `types/App/Entity/User/email.html.twig` | Clickable mailto link |
| `types/App/Entity/Part/bicycle.html.twig` | Linked relation via `admin_entity_url()` |

### Base Layout Integration

```yaml
# config/packages/kachnitel_admin.yaml
kachnitel_admin:
    base_layout: 'base.html.twig'
    required_role: null  # Demo: no global auth restriction
    theme: 'theme/tailwind_dark.html.twig'
```

The bundle integrates with your app's layout via blocks: `title`, `headerTitle`, `headerButtons`, `content`.

---

## Installation Notes

<details>
<summary><strong>Manual setup (what was configured from scratch)</strong></summary>

#### Bundle Config (`config/packages/kachnitel_admin.yaml`)
```yaml
kachnitel_admin:
    base_layout: 'base.html.twig'
    required_role: null
```

#### Security (`config/packages/security.yaml`)
- User entity as provider
- Form login at `/login`
- Access control: `/admin` requires `ROLE_USER`

#### Symlinked Development
```json
// composer.json
{
    "repositories": [{ "type": "path", "url": "../FrdAdminBundle" }],
    "require": { "kachnitel/admin-bundle": "@dev" }
}
```
After bundle changes: `php bin/console cache:clear`

</details>

<details>
<summary><strong>Batch Actions Stimulus setup</strong></summary>

Batch actions require manually registering the bundle's Stimulus controller.

**`assets/controllers.json`**:
```json
{
    "controllers": {
        "@kachnitel/admin-bundle": {
            "batch-select": { "enabled": true, "fetch": "eager" }
        }
    }
}
```

**`importmap.php`**:
```php
'@kachnitel/admin-bundle/batch-select_controller.js' => [
    'path' => '@kachnitel/admin-bundle/controllers/batch-select_controller.js',
],
```

**`assets/stimulus_bootstrap.js`**:
```js
import BatchSelectController from '@kachnitel/admin-bundle/batch-select_controller.js';
app.register('batch-select', BatchSelectController);
```

</details>

---

## Upgrade Notes

<details>
<summary><strong>v0.9 Breaking Changes</strong></summary>

**1. `DataSourceInterface` namespace change**

Contracts extracted to `kachnitel/datasource-contracts`:

```diff
- use Kachnitel\AdminBundle\DataSource\DataSourceInterface;
- use Kachnitel\AdminBundle\DataSource\ColumnMetadata;
- use Kachnitel\AdminBundle\DataSource\FilterMetadata;
- use Kachnitel\AdminBundle\DataSource\PaginatedResult;
+ use Kachnitel\DataSourceContracts\DataSourceInterface;
+ use Kachnitel\DataSourceContracts\ColumnMetadata;
+ use Kachnitel\DataSourceContracts\FilterMetadata;
+ use Kachnitel\DataSourceContracts\PaginatedResult;
```

Also update `config/services.yaml`:
```diff
  _instanceof:
-     Kachnitel\AdminBundle\DataSource\DataSourceInterface:
+     Kachnitel\DataSourceContracts\DataSourceInterface:
          tags:
-             - { name: 'Kachnitel\AdminBundle\DataSource\DataSourceInterface' }
+             - { name: 'Kachnitel\DataSourceContracts\DataSourceInterface' }
```

**2. PHP 8.4 required**

**3. New required packages** (pulled automatically via Composer)
- `kachnitel/datasource-contracts`
- `kachnitel/entity-expression-language`
- `symfony/ux-autocomplete`

</details>

---

## Running Tests

```bash
# All tests (excludes browser tests)
vendor/bin/phpunit

# By feature group
vendor/bin/phpunit --group archive
vendor/bin/phpunit --group datasource-contracts

# By directory
vendor/bin/phpunit tests/Entity
vendor/bin/phpunit tests/DataSource
vendor/bin/phpunit tests/Controller

# Browser tests (requires geckodriver)
vendor/bin/bdi detect drivers
vendor/bin/phpunit -c phpunit-browser.xml
```

<details>
<summary><strong>Test setup pattern</strong></summary>

Tests use `setUpBeforeClass` / `tearDownAfterClass` for database lifecycle:

```php
public static function setUpBeforeClass(): void
{
    parent::setUpBeforeClass();
    self::bootKernel();

    $entityManager = self::getContainer()->get('doctrine')->getManager();
    $schemaTool = new SchemaTool($entityManager);
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

    $schemaTool->dropSchema($metadata);
    $schemaTool->createSchema($metadata);

    self::ensureKernelShutdown();
}
```

LiveComponent tests use `InteractsWithLiveComponents` trait with real entity-based authentication (not `InMemoryUser`).

</details>

---

## File Structure

```
src/
├── Controller/
│   ├── AdminController.php               # Custom controller (generic route)
│   └── SecurityController.php
├── DataSource/
│   └── VendorCatalogDataSource.php       # Uses kachnitel/datasource-contracts
├── Entity/
│   ├── User.php
│   ├── Bicycle.php
│   └── Part.php                          # archived field + archiveExpression
└── Command/
    ├── LoadDemoDataCommand.php           # Seeds ~20% of parts as archived
    └── CreateDemoUserCommand.php

migrations/
├── Version20251205231658.php             # Initial schema
├── Version20251210174711.php             # Add password to users
├── Version20260110120000.php             # Add datetime fields
└── Version20260422000000.php             # Add archived to parts (v0.9)

templates/
├── base.html.twig
├── admin/
│   ├── index.html.twig                   # Homepage with feature cards
│   └── entity.html.twig                  # Generic entity template
├── security/login.html.twig
└── bundles/KachnitelAdminBundle/types/
    ├── boolean/_preview.html.twig
    ├── App/Entity/User/email.html.twig
    └── App/Entity/Part/bicycle.html.twig

config/
├── packages/kachnitel_admin.yaml
├── services.yaml                         # datasource-contracts namespace
└── ...

tests/
├── Entity/
│   ├── EntityAttributeTest.php           # #[Admin] attribute + getter/setter tests
│   └── PartArchiveTest.php               # Archive feature tests
├── DataSource/
│   └── VendorCatalogDataSourceTest.php
└── Controller/
    ├── AdminControllerTest.php           # LiveComponent tests
    ├── BundleAdminControllerTest.php     # Bundle routes + archive toggle
    └── SecurityControllerTest.php
```
