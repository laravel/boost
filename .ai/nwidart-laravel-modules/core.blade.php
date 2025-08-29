## Laravel Modules Core (v6+)

- Use the `search-docs` tool to find Laravel Modules specific documentation when needed.
- Use the `php artisan module:make [ModuleName]` command to create new modules.
- Use the `php artisan module:make-controller [ControllerName] [ModuleName]` command to create controllers within modules.
- Use the `php artisan module:make-model [ModelName] [ModuleName]` command to create models within modules.
- Use the `php artisan module:make-migration [migration_name] [ModuleName]` command to create migrations within modules.
- Each module should be self-contained with its own routes, controllers, models, views, and migrations.
- Modules are located in the `Modules/` directory by default.

## Laravel Modules Best Practices

### Module Structure
- Follow the standard module structure: Controllers, Models, Views, Routes, Migrations, Database (seeders/factories), Config, etc.
- Keep module dependencies minimal - avoid tight coupling between modules.
- Use proper namespacing: `Modules\[ModuleName]\Http\Controllers`, `Modules\[ModuleName]\Entities`, etc.

### Routing
- Define routes in `Modules/[ModuleName]/Routes/web.php` for web routes.
- Define API routes in `Modules/[ModuleName]/Routes/api.php` for API routes.
- Use route groups with proper prefixes and namespaces:
@verbatim
```php
Route::prefix('modulename')->group(function() {
    Route::get('/', [ModuleController::class, 'index']);
});
```
@endverbatim

### Models and Entities
- Place models in `Modules/[ModuleName]/Models/` directory.
- Use proper namespace: `Modules\[ModuleName]\Models\[ModelName]`.
- Follow Laravel model conventions within the module context.

### Controllers
- Place controllers in `Modules/[ModuleName]/Http/Controllers/` directory.
- Use proper namespace: `Modules\[ModuleName]\Http\Controllers\[ControllerName]`.
- Import models from the correct module namespace.

### Views
- Place views in `Modules/[ModuleName]/Resources/views/` directory.
- Reference views with module prefix: `modulename::viewname`.
- Create layouts specific to modules when needed.

### Migrations
- Run `php artisan module:migrate [ModuleName]` to run migrations for a specific module.
- Run `php artisan module:migrate` to run migrations for all modules.
- Place migrations in `Modules/[ModuleName]/Database/Migrations/` directory.

### Module Service Providers
- Each module has its own service provider in `Modules/[ModuleName]/Providers/[ModuleName]ServiceProvider.php`.
- Register module-specific services, routes, and views in the service provider.
- Use the service provider to publish module assets and configuration.

### Configuration
- Place module configuration in `Modules/[ModuleName]/Config/config.php`.
- Access module config with: `config('modulename.key')`.

### Module Commands

#### Basic Module Management
- `php artisan module:make [ModuleName]` - Create a new module
- `php artisan module:list` - List all modules
- `php artisan module:enable [ModuleName]` - Enable a module
- `php artisan module:disable [ModuleName]` - Disable a module  
- `php artisan module:use [ModuleName]` - Set active module context
- `php artisan module:unuse` - Clear active module context
- `php artisan module:update [ModuleName]` - Update a module
- `php artisan module:seed [ModuleName]` - Seed a specific module

#### Migration Commands
- `php artisan module:migrate [ModuleName]` - Run module migrations
- `php artisan module:migrate-rollback [ModuleName]` - Rollback module migrations
- `php artisan module:migrate-refresh [ModuleName]` - Refresh module migrations
- `php artisan module:migrate-reset [ModuleName]` - Reset module migrations
- `php artisan module:publish-migration [ModuleName]` - Publish module migrations

#### Publishing Commands
- `php artisan module:publish-config [ModuleName]` - Publish module configuration
- `php artisan module:publish-translation [ModuleName]` - Publish translation files

#### Generator Commands
- `php artisan module:make-command [CommandName] [ModuleName]` - Create console command
- `php artisan module:make-controller [ControllerName] [ModuleName]` - Create controller
- `php artisan module:make-model [ModelName] [ModuleName]` - Create model
- `php artisan module:make-migration [MigrationName] [ModuleName]` - Create migration
- `php artisan module:make-seed [SeederName] [ModuleName]` - Create seeder
- `php artisan module:make-provider [ProviderName] [ModuleName]` - Create service provider
- `php artisan module:make-middleware [MiddlewareName] [ModuleName]` - Create middleware
- `php artisan module:make-mail [MailName] [ModuleName]` - Create mail class
- `php artisan module:make-notification [NotificationName] [ModuleName]` - Create notification
- `php artisan module:make-listener [ListenerName] [ModuleName]` - Create event listener
- `php artisan module:make-request [RequestName] [ModuleName]` - Create form request
- `php artisan module:make-event [EventName] [ModuleName]` - Create event
- `php artisan module:make-job [JobName] [ModuleName]` - Create job
- `php artisan module:make-factory [FactoryName] [ModuleName]` - Create model factory
- `php artisan module:make-policy [PolicyName] [ModuleName]` - Create policy
- `php artisan module:make-rule [RuleName] [ModuleName]` - Create validation rule
- `php artisan module:make-resource [ResourceName] [ModuleName]` - Create API resource
- `php artisan module:make-test [TestName] [ModuleName]` - Create test
- `php artisan module:route-provider [ModuleName]` - Create route service provider

### Testing Modules
- Place tests in `Modules/[ModuleName]/Tests/` directory.
- Use proper namespace for test classes.
- Test module functionality in isolation when possible.
- Use Laravel's testing features with proper module context.

### Module Publishing
- Use `php artisan module:publish [ModuleName]` to publish module assets.
- Configure publishable assets in the module's service provider.
- Keep published assets organized and documented.

## Module Facade Methods
The `Module` facade provides methods for programmatic module management:

### Module Discovery
- `Module::all()` - Get all modules
- `Module::find('blog')` - Find a specific module by name
- `Module::has('blog')` - Check if a module exists
- `Module::count()` - Get total number of modules

### Module Status Management
- `Module::allEnabled()` - Get all enabled modules
- `Module::allDisabled()` - Get all disabled modules
- `Module::getByStatus(1)` - Get modules by active/inactive status

### Module Operations
- `Module::register()` - Register modules
- `Module::boot()` - Boot all available modules
- `Module::install('module-name')` - Install a specific module
- `Module::update('module-name')` - Update module dependencies

### Path and Asset Methods
- `Module::getPath()` - Get module path
- `Module::assetPath('name')` - Get assets path for a module
- `Module::asset('module:path')` - Get asset URL from a specific module

### Advanced Methods
- `Module::macro()` - Add custom methods to module repository
- `Module::getRequirements('module')` - Get required modules for a specific module

## Module Instance Methods
When working with a specific module instance, use these methods:

### Module Information
@verbatim
```php
$module = Module::find('blog');
$module->getName();        // Get module name
$module->getLowerName();   // Get lowercase name
$module->getStudlyName();  // Get StudlyCase name
```
@endverbatim

### Module Paths
@verbatim
```php
$module->getPath();                // Get module path
$module->getExtraPath('Assets');   // Get additional module paths
```
@endverbatim

### Module State Management
@verbatim
```php
$module->enable();   // Enable the module
$module->disable();  // Disable the module
$module->delete();   // Delete the module
```
@endverbatim

### Module Dependencies
@verbatim
```php
$module->getRequires();  // Get array of module requirements (aliases)
```
@endverbatim

## Module Events and Listeners
Create and manage events within modules:

### Creating Events and Listeners
@verbatim
```php
// Create an event
php artisan module:make-event BlogPostWasUpdated Blog

// Create a listener
php artisan module:make-listener NotifyAdminOfNewPost Blog
```
@endverbatim

### Registering Events

#### Method 1: Manual Registration in Service Provider
@verbatim
```php
$this->app['events']->listen(
    BlogPostWasUpdated::class, 
    NotifyAdminOfNewPost::class
);
```
@endverbatim

#### Method 2: Create EventServiceProvider
@verbatim
```php
<?php
namespace Modules\Blog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BlogPostWasUpdated::class => [
            NotifyAdminOfNewPost::class,
        ],
    ];
}
```
@endverbatim

## Publishing Modules as Packages
Distribute modules as Composer packages:

### Setup Requirements
1. Install required packages:
   - `nwidart/laravel-modules`
   - `joshbrw/laravel-module-installer`

### Package Configuration
Configure `composer.json`:
@verbatim
```json
{
    "type": "laravel-module",
    "extra": {
        "module-dir": "Custom"  // Optional custom directory
    }
}
```
@endverbatim

### Repository Naming Convention
- Use format: `<namespace>/<name>-module`
- Example: `https://github.com/nWidart/article-module`
- Installs to: `Module/Article` directory

### Installation Command
@verbatim
```bash
composer require nwidart/article-module
```
@endverbatim

## Inter-Module Communication
- Use Laravel's event system for loose coupling between modules.
- Avoid direct dependencies between modules when possible.
- Use contracts/interfaces for module interaction when needed.
- Consider using service containers for shared functionality.