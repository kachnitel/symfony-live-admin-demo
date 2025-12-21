# Admin Bundle Demo

A minimal demonstration of the **kachnitel/admin-bundle** showcasing LiveComponents for entity management.

## Quick Start

```bash
# Install dependencies
composer install

# Create database and load demo data
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:load-demo-data
php bin/console app:create-demo-user

# Start server
symfony server:start
```

**Login:** `user@example.com` / `password`

## Database

SQLite database located at `var/data.db` with sample data:
- 3 Users (with active/inactive status)
- 3 Bicycles (Trek, Specialized, Cannondale)
- 14 Parts (wheels, frames, forks, grips, etc.)

## Demo Features

### 1. Two Implementation Approaches

#### Custom Controller (`AdminController`)
- **Route Pattern**: `/custom-admin/{entity}`
- **Template**: Single generic template at `templates/admin/entity.html.twig`
- **Reduces Duplication**: One template handles all entities via parameter
- **Examples**:
  - `/custom-admin/user`
  - `/custom-admin/bicycle`
  - `/custom-admin/part`

#### Bundle's GenericAdminController
- **Route Pattern**: `/admin` (auto-discovery via `#[Admin]` attribute)
- **Zero-Code**: No controller needed, entities auto-discovered
- **Examples**:
  - `/admin` - Dashboard listing all entities
  - `/admin/user` - Auto-generated User management
  - `/admin/bicycle` - Auto-generated Bicycle management
  - `/admin/part` - Auto-generated Part management

### 2. Entity Configuration

All entities use the `#[Admin]` attribute for auto-discovery:

**User.php**
```php
#[Admin(label: 'Users', icon: 'person')]
```

**Bicycle.php**
```php
#[Admin(label: 'Bicycles', icon: 'pedal_bike')]
```

**Part.php** (with batch actions enabled)
```php
#[Admin(label: 'Parts', icon: 'settings', enableBatchActions: true)]
```

### 3. Template Overrides

Custom rendering via template overrides in `templates/bundles/KachnitelAdminBundle/`:

#### Boolean Fields
**Location**: `types/boolean/_preview.html.twig`
- Renders as colored badges: ✓ Yes (green) / ✗ No (red)
- Affects all boolean fields across all entities

#### User Email Field
**Location**: `types/App/Entity/User/email.html.twig`
- Entity-specific override for User.email
- Renders as clickable mailto link with icon

### 4. Base Layout Integration

**Config**: `config/packages/kachnitel_admin.yaml`
```yaml
kachnitel_admin:
    base_layout: 'base.html.twig'
    required_role: null  # For demo purposes
```

The bundle integrates with the app's base layout using these blocks:
- `{% block title %}` - Page title
- `{% block headerTitle %}` - Page header
- `{% block headerButtons %}` - Action buttons
- `{% block content %}` - Main content

## Installation Notes

This demo shows the complete manual setup process. For new projects, many of these steps can be automated via Symfony Flex recipes.

### What Was Configured Manually

#### 1. Bundle Configuration (`config/packages/kachnitel_admin.yaml`)
```yaml
kachnitel_admin:
    base_layout: 'base.html.twig'
    required_role: null
```

#### 2. Routes (imported via attribute-based routing)
The bundle's routes are auto-discovered. For custom routes, see `src/Controller/AdminController.php`.

#### 3. Security (`config/packages/security.yaml`)
- User entity as provider
- Form login with `/login` path
- Access control: `/admin` requires `ROLE_USER`

#### 4. Stimulus Controller for Batch Actions

For batch operations (shift-click multi-select, batch delete), the bundle provides a Stimulus controller that needs to be registered:

**`assets/controllers.json`** - Add the bundle's controller:
```json
{
    "controllers": {
        "@kachnitel/admin-bundle": {
            "batch-select": {
                "enabled": true,
                "fetch": "eager",
                "autoimport": {}
            }
        }
    }
}
```

**`importmap.php`** - Add the importmap entry:
```php
return [
    // ... other entries
    '@kachnitel/admin-bundle/batch-select_controller.js' => [
        'path' => '@kachnitel/admin-bundle/batch-select_controller.js',
    ],
];
```

#### 5. Symlinked Development

This demo uses a symlinked local version of the bundle for development:

**`composer.json`**:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../FrdAdminBundle"
        }
    ],
    "require": {
        "kachnitel/admin-bundle": "@dev"
    }
}
```

After changes to the bundle, clear cache:
```bash
php bin/console cache:clear
```

## Running the Demo

1. **Start Server**:
   ```bash
   symfony server:start
   ```

2. **Visit**:
   - Login: `http://localhost:8000/login`
   - Bundle Admin: `http://localhost:8000/admin`
   - Custom Admin: `http://localhost:8000/custom-admin/user`

3. **Load Demo Data**:
   ```bash
   php bin/console app:load-demo-data
   ```

## Running Tests

The demo includes comprehensive tests using Symfony's test framework and the LiveComponent test helpers.

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suites
vendor/bin/phpunit tests/Entity           # Unit tests for entities
vendor/bin/phpunit tests/Controller       # Integration/functional tests
```

### Test Setup

Tests use `setUpBeforeClass`/`tearDownAfterClass` for database lifecycle:

```php
public static function setUpBeforeClass(): void
{
    parent::setUpBeforeClass();
    self::bootKernel();

    $entityManager = self::getContainer()->get('doctrine')->getManager();
    $schemaTool = new SchemaTool($entityManager);
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

    // Drop and recreate for clean state
    $schemaTool->dropSchema($metadata);
    $schemaTool->createSchema($metadata);

    self::ensureKernelShutdown();
}
```

### Test Coverage

**Entity Tests** (7 tests, 25 assertions) - ALL PASSING
- Verifies `#[Admin]` attributes on all entities
- Tests entity getters/setters
- Tests bicycle-part relationships

**LiveComponent Tests** - Uses `InteractsWithLiveComponents` trait
- Tests EntityList component rendering
- Uses real `User` entity for authentication (not `InMemoryUser`)

**Functional Tests** - Tests bundle routes with authentication
- Creates authenticated client with `loginUser()`
- Tests dashboard, entity pages, and 404 handling

## File Structure

```
src/
├── Controller/
│   ├── AdminController.php          # Custom controller with generic route
│   └── SecurityController.php       # Login/logout handling
├── Entity/
│   ├── User.php                     # #[Admin] attribute
│   ├── Bicycle.php                  # #[Admin] attribute
│   └── Part.php                     # #[Admin(enableBatchActions: true)]
└── Command/
    ├── LoadDemoDataCommand.php      # Sample data loader
    └── CreateDemoUserCommand.php    # Demo user creator

assets/
├── app.js                           # Main JS entrypoint
├── controllers.json                 # Stimulus controller registry
└── stimulus_bootstrap.js            # Stimulus initialization

templates/
├── base.html.twig                   # App base layout
├── admin/
│   ├── index.html.twig              # Home dashboard
│   └── entity.html.twig             # Generic entity template
├── security/
│   └── login.html.twig              # Login form
└── bundles/
    └── KachnitelAdminBundle/
        └── types/
            ├── boolean/_preview.html.twig              # Boolean override
            └── App/Entity/User/email.html.twig         # User email override

config/
├── packages/
│   ├── kachnitel_admin.yaml         # Bundle configuration
│   └── security.yaml                # Security configuration
└── routes/
    └── security.yaml                # Login/logout routes

tests/
├── Entity/                          # Unit tests
│   ├── UserTest.php
│   ├── BicycleTest.php
│   └── PartTest.php
└── Controller/                      # Functional tests
    ├── AdminControllerTest.php      # LiveComponent tests
    ├── BundleAdminControllerTest.php # Bundle route tests
    └── SecurityControllerTest.php   # Auth tests
```

## Key Concepts Demonstrated

1. **Reduced Duplication**: Single generic template vs separate templates per entity
2. **Auto-Discovery**: Entities with `#[Admin]` attribute automatically available
3. **Template Hierarchy**: Global type overrides vs entity-specific overrides
4. **Base Layout Integration**: Bundle extends your app's layout
5. **Zero-Code Admin**: GenericAdminController requires no custom code
6. **Custom Routes**: Mix bundle routes with your own controller when needed
7. **Batch Operations**: Multi-select with Shift+Click and bulk delete
8. **Security Integration**: Form login with entity-based user provider
9. **Testing Patterns**: LiveComponent testing with real entities
