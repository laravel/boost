---
name: pennant-features
description: >-
  Manage feature flags with Laravel Pennant. MUST activate when creating, checking, or toggling
  feature flags; showing or hiding features conditionally; implementing A/B testing; working with
  @feature directive; or when the user mentions feature flags, feature toggles, Pennant, conditional
  features, rollouts, or gradually enabling features.
---
# Pennant Features

## When to Use This Skill

Activate this skill when:
- Creating new feature flags
- Checking feature availability in code
- Managing feature rollouts
- Implementing A/B testing scenarios
- Working with feature flag scopes

## Core Patterns

### Overview

This application uses Laravel Pennant for feature flag management, providing a flexible system for controlling feature availability across different organizations and user types.

### Documentation

Use the `search-docs` tool, in combination with existing codebase conventions, to assist the user effectively with feature flags.

## Basic Usage

### Defining Features

```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

```php
use Laravel\Pennant\Feature;

if (Feature::active('new-dashboard')) {
    // Show new dashboard
}

// Or with a scope
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

## Feature Drivers

Pennant supports different storage drivers:
- **database** - Persists feature values in the database
- **array** - In-memory storage (useful for testing)

## Common Patterns

### Class-Based Features

```php
namespace App\Features;

class NewDashboard
{
    public function resolve(User $user): bool
    {
        return $user->isAdmin();
    }
}
```

### Activating/Deactivating Features

```php
Feature::activate('new-dashboard');
Feature::deactivate('new-dashboard');

// For specific scope
Feature::for($user)->activate('new-dashboard');
```

## Common Pitfalls

- Forgetting to scope features when checking for specific users/entities
- Not using the `search-docs` tool for detailed Pennant documentation
- Not following existing codebase conventions for feature flag naming
