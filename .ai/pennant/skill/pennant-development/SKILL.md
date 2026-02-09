---
name: pennant-development
description: "Manages feature flags with Laravel Pennant. Activates when creating, checking, or toggling feature flags; showing or hiding features conditionally; implementing A/B testing; working with @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional features, rollouts, or gradually enabling features."
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

```php
# Defining Features
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

```php
# Checking Features
if (Feature::active('new-dashboard')) {
    // Feature is active
}

// With scope
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}
```

### Blade Directive

```blade
# Blade Directive
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
```

### Activating / Deactivating

```php
# Activating Features
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
```

## Verification

1. Check feature flag is defined
2. Test with different scopes/users

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
