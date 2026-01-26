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

Activate this skill when:

- Creating or checking feature flags
- Managing feature rollouts
- Implementing A/B testing

## Documentation

Use `search-docs` for detailed Pennant patterns and documentation.

## Basic Usage

### Defining Features

<code-snippet name="Defining Features" lang="php">
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
</code-snippet>

### Checking Features

<code-snippet name="Checking Features" lang="php">
if (Feature::active('new-dashboard')) {
    // Feature is active
}

// With scope
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}
</code-snippet>

### Blade Directive

<code-snippet name="Blade Directive" lang="blade">
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
</code-snippet>

### Activating / Deactivating

<code-snippet name="Activating Features" lang="php">
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
</code-snippet>

## Verification

1. Check feature flag is defined
2. Test with different scopes/users

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
