---
name: managing-feature-flags
description: Managing feature flags with Laravel Pennant. Use when implementing feature toggles, A/B testing, gradual rollouts, or user-specific features.
---

# Managing Feature Flags with Pennant

## When to use this skill

Use this skill when the user asks about:
- Implementing feature flags
- Gradual feature rollouts
- A/B testing
- User-specific feature access
- Feature flag storage and caching

## Defining Features

Create feature classes:

```php
// app/Features/NewDashboard.php
namespace App\Features;

use Illuminate\Support\Lottery;

class NewDashboard
{
    public function resolve(User $user): mixed
    {
        // Always enabled for admins
        if ($user->isAdmin()) {
            return true;
        }

        // 50% rollout for other users
        return Lottery::odds(1, 2)->choose();
    }
}
```

Or define inline:

```php
// app/Providers/AppServiceProvider.php
use Laravel\Pennant\Feature;

public function boot(): void
{
    Feature::define('new-dashboard', function (User $user) {
        return $user->isAdmin();
    });

    // With lottery for gradual rollout
    Feature::define('new-api', function (User $user) {
        return Lottery::odds(1, 10)->choose(); // 10% of users
    });
}
```

## Checking Features

```php
use Laravel\Pennant\Feature;

// Check if feature is active
if (Feature::active('new-dashboard')) {
    // Show new dashboard
}

// Check for specific user
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}

// In Blade templates
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature

// Get feature value (for A/B variants)
$variant = Feature::value('checkout-button'); // 'blue', 'green', etc.
```

## Rich Feature Values

Return more than just true/false:

```php
Feature::define('checkout-button', function (User $user) {
    return match (true) {
        $user->isAdmin() => 'admin-variant',
        $user->isPremium() => 'premium-variant',
        default => Arr::random(['blue', 'green', 'red']),
    };
});

// Usage
$color = Feature::value('checkout-button');
```

## Scopes

Features can be scoped to different models:

```php
// Default scope is authenticated user
Feature::active('new-feature'); // Uses auth()->user()

// Explicit scope
Feature::for($team)->active('team-feature');
Feature::for($organization)->active('org-feature');

// Multiple scopes
Feature::for([$user, $team])->active('feature');

// Null scope (global features)
Feature::for(null)->active('maintenance-mode');
```

## Activating/Deactivating

Manually control features:

```php
// Activate for specific user
Feature::for($user)->activate('new-dashboard');

// Activate with value
Feature::for($user)->activate('variant', 'blue');

// Deactivate
Feature::for($user)->deactivate('new-dashboard');

// Forget (re-evaluate next time)
Feature::for($user)->forget('new-dashboard');

// Activate for everyone
Feature::activateForEveryone('maintenance-mode');
Feature::deactivateForEveryone('maintenance-mode');
```

## Middleware

Protect routes with feature flags:

```php
// routes/web.php
Route::middleware('feature:new-dashboard')->group(function () {
    Route::get('/dashboard', NewDashboardController::class);
});

// With redirect
Route::get('/beta', BetaController::class)
    ->middleware('feature:beta-access,/upgrade');

// Abort if not active
Route::get('/admin', AdminController::class)
    ->middleware('feature:admin-panel,403');
```

## Blade Directives

```blade
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-legacy-dashboard />
@endfeature

{{-- With value --}}
@feature('button-color', 'blue')
    <button class="bg-blue-500">Click</button>
@elsefeature('button-color', 'green')
    <button class="bg-green-500">Click</button>
@else
    <button class="bg-gray-500">Click</button>
@endfeature
```

## Storage Drivers

Configure in `config/pennant.php`:

```php
'default' => env('PENNANT_STORE', 'database'),

'stores' => [
    'database' => [
        'driver' => 'database',
        'table' => 'features',
    ],
    'array' => [
        'driver' => 'array',
    ],
],
```

Run migrations:
```bash
php artisan vendor:publish --tag=pennant-migrations
php artisan migrate
```

## Eager Loading

Prevent N+1 queries:

```php
// Load features for multiple users
Feature::for($users)->load(['new-dashboard', 'beta-features']);

// Load all defined features
Feature::for($users)->loadAll();

// In queries
$users = User::all();
Feature::for($users)->loadMissing(['new-dashboard']);
```

## Events

Listen to feature changes:

```php
// EventServiceProvider
use Laravel\Pennant\Events\FeatureResolved;
use Laravel\Pennant\Events\FeatureUpdated;

protected $listen = [
    FeatureResolved::class => [
        LogFeatureResolution::class,
    ],
    FeatureUpdated::class => [
        NotifyFeatureChange::class,
    ],
];
```

## Artisan Commands

```bash
# Purge resolved features (force re-evaluation)
php artisan pennant:purge new-dashboard

# Purge all features for scope
php artisan pennant:purge --except=stable-feature

# Clear feature cache
php artisan pennant:clear
```

## Testing

```php
use Laravel\Pennant\Feature;

public function test_new_dashboard_for_admins(): void
{
    Feature::define('new-dashboard', true);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSee('New Dashboard');
}

public function test_feature_rollout(): void
{
    // Activate for specific test
    Feature::activate('beta-feature');

    // Or deactivate
    Feature::deactivate('beta-feature');

    // Test code...
}
```

## Best Practices

1. **Use class-based features** - Better organization and testing
2. **Gradual rollouts** - Use Lottery for percentage-based rollouts
3. **Cache appropriately** - Database driver caches by default
4. **Clean up old flags** - Remove features after full rollout
5. **Monitor usage** - Track feature flag evaluations
6. **Test both paths** - Test enabled and disabled states
