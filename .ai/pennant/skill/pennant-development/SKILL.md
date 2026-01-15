---
name: pennant-development
description: >-
  Manages feature flags with Laravel Pennant. Activates when creating, checking, or toggling
  feature flags; showing or hiding features conditionally; implementing A/B testing; working with
  @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional
  features, rollouts, or gradually enabling features.
---
# Pennant Features

## When to Apply

- Creating or checking feature flags
- Managing feature rollouts
- Implementing A/B testing

## Core Patterns

### Defining Features

```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

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

```blade
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
```

### Activating/Deactivating

```php
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
```

## Verification

1. Check feature flag is defined
2. Test with different scopes/users
3. Use `search-docs` with "pennant" for more patterns

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
