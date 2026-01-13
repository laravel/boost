---
name: using-fluxui
description: >-
  Build UIs with Flux UI Free components. MUST activate when creating buttons, forms, modals,
  inputs, dropdowns, checkboxes, or UI components; replacing HTML form elements with Flux;
  working with flux: components; or when the user mentions Flux, component library, UI components,
  form fields, or asks about available Flux components.
---
# Using Flux UI

## When to Use This Skill

Activate this skill when:
- Creating new UI components or pages
- Working with forms, modals, or interactive elements
- Styling components with Flux UI patterns
- Checking available Flux components

## Core Patterns

### Overview

This project is using the **free edition** of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.

Flux UI is a component library for Livewire. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.

### Basic Usage

You should use Flux UI components when available. Fallback to standard Blade components if Flux is unavailable.

```blade
<flux:button variant="primary"/>
```

### Documentation

If available, use the `search-docs` tool to get the exact documentation and code snippets available for this project.

## Available Components (Free Edition)

This is correct as of Boost installation, but there may be additional components within the codebase.

**Available in Free:**
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip

**Pro-only (not available):**
accordion, autocomplete, calendar, card, chart, command, composer, context, date-picker, editor, file-upload, kanban, pagination, pillbox, popover, slider, table, tabs, time-picker, toast

## Common Patterns

### Buttons

```blade
<flux:button variant="primary">Primary</flux:button>
<flux:button variant="secondary">Secondary</flux:button>
```

### Form Fields

```blade
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
```

### Modals

```blade
<flux:modal wire:model="showModal">
    <flux:heading>Modal Title</flux:heading>
    <p>Modal content here</p>
    <flux:button wire:click="$set('showModal', false)">Close</flux:button>
</flux:modal>
```

## Common Pitfalls

- Trying to use Pro-only components in the free edition
- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
