---
name: fluxui-development
description: >-
  Develops UIs with Flux UI Free components. Activates when creating buttons, forms, modals,
  inputs, dropdowns, checkboxes, or UI components; replacing HTML form elements with Flux;
  working with flux: components; or when the user mentions Flux, component library, UI components,
  form fields, or asks about available Flux components.
---
# Flux UI Development

## When to Apply

Activate this skill when:
- Creating UI components or pages
- Working with forms, modals, or interactive elements
- Checking available Flux components

## Documentation

Use `search-docs` for detailed Flux UI patterns and documentation.

## Basic Usage

This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.

Flux UI is a component library for Livewire. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.

You should use Flux UI components when available. Fallback to standard Blade components if Flux is unavailable.

<code-snippet name="Basic Button" lang="blade">
<flux:button variant="primary">Click me</flux:button>
</code-snippet>

## Available Components (Free Edition)

Available: avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip

## Common Patterns

### Form Fields

<code-snippet name="Form Field" lang="blade">
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
</code-snippet>

### Modals

<code-snippet name="Modal" lang="blade">
<flux:modal wire:model="showModal">
    <flux:heading>Title</flux:heading>
    <p>Content</p>
</flux:modal>
</code-snippet>

## Verification

1. Check component renders correctly
2. Test interactive states
3. Verify mobile responsiveness

## Common Pitfalls

- Trying to use Pro-only components in the free edition
- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
