# Admin Bundle Demo

A minimal demonstration of the **kachnitel/admin-bundle** showcasing LiveComponents for entity management.

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

**Part.php**
```php
#[Admin(label: 'Parts', icon: 'settings')]
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
    required_role: 'PUBLIC_ACCESS'
```

The bundle integrates with the app's base layout using these blocks:
- `{% block title %}` - Page title
- `{% block headerTitle %}` - Page header
- `{% block headerButtons %}` - Action buttons
- `{% block content %}` - Main content

## Running the Demo

1. **Start Server**:
   ```bash
   symfony server:start
   ```

2. **Visit**:
   - Home: `http://localhost:8000/`
   - Custom Admin: `http://localhost:8000/custom-admin/user`
   - Bundle Admin: `http://localhost:8000/admin`

3. **Load More Data** (if needed):
   ```bash
   bin/console app:load-demo-data
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

### Test Coverage

**Entity Tests** (7 tests, 25 assertions) - ✅ ALL PASSING
- Verifies `#[Admin]` attributes on all entities
- Tests entity getters/setters
- Tests bicycle-part relationships

**LiveComponent Tests** - Uses `InteractsWithLiveComponents` trait
- Tests EntityList component rendering
- Automatic database setup/teardown per test

**Functional Tests** - Tests bundle routes
- Database lifecycle management with `setUpBeforeClass`/`tearDownAfterClass`
- Tests GenericAdminController routes

## File Structure

```
src/
├── Controller/
│   └── AdminController.php          # Custom controller with generic route
├── Entity/
│   ├── User.php                     # #[Admin] attribute
│   ├── Bicycle.php                  # #[Admin] attribute
│   └── Part.php                     # #[Admin] attribute
└── Command/
    └── LoadDemoDataCommand.php      # Sample data loader

templates/
├── base.html.twig                   # App base layout
├── admin/
│   ├── index.html.twig              # Home dashboard
│   └── entity.html.twig             # Generic entity template
└── bundles/
    └── KachnitelAdminBundle/
        └── types/
            ├── boolean/_preview.html.twig              # Boolean override
            └── App/Entity/User/email.html.twig         # User email override

config/packages/
└── kachnitel_admin.yaml             # Bundle configuration
```

## Key Concepts Demonstrated

1. **Reduced Duplication**: Single generic template vs separate templates per entity
2. **Auto-Discovery**: Entities with `#[Admin]` attribute automatically available
3. **Template Hierarchy**: Global type overrides vs entity-specific overrides
4. **Base Layout Integration**: Bundle extends your app's layout
5. **Zero-Code Admin**: GenericAdminController requires no custom code
6. **Custom Routes**: Mix bundle routes with your own controller when needed
