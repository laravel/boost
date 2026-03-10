---
name: pennant-development
description: "Use when the user works with Laravel Pennant, the official Laravel feature flag package. Trigger whenever the query mentions Pennant by name, or involves feature flags or feature toggles within a Laravel project. Common tasks include defining feature flags, checking if features are active, class-based features in app/Features/, Blade @feature directives, scoping flags to users or teams, gradual percentage rollouts, building custom Pennant storage drivers, protecting routes with feature flag middleware, testing feature flags with Pest or PHPUnit, and listening to Pennant events. Also trigger for Laravel-specific discussions of canary releases, dark launches, A/B testing with feature flags, or gradual rollouts. Do not trigger for generic Laravel config, authorization policies, authentication, or non-Pennant feature management systems."
license: MIT
metadata:
  author: laravel
---
# Pennant Features

## When to Apply

Activate this skill when:

- Creating or checking feature flags
- Managing feature rollouts
- Implementing A/B testing

## Documentation

Use `search-docs` for detailed Pennant patterns and documentation.

## Basic Usage

### Defining Features

<!-- Defining Features -->
```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

<!-- Checking Features -->
```php
if (Feature::active('new-dashboard')) {
    // Feature is active
}

// With scope
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}
```

### Blade Directive

<!-- Blade Directive -->
```blade
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
```

### Activating / Deactivating

<!-- Activating Features -->
```php
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
```

## Verification

1. Check feature flag is defined
2. Test with different scopes/users

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
