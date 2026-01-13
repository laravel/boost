---
name: using-fluxui
description: >-
  Build UIs with Flux UI Pro components. MUST activate when creating buttons, forms, modals,
  inputs, tables, charts, date pickers, or UI components; replacing HTML elements with Flux;
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

This project is using the **Pro version** of Flux UI. It has full access to the free components and variants, as well as full access to the Pro components and variants.

Flux UI is a component library for Livewire. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.

### Basic Usage

You should use Flux UI components when available. Fallback to standard Blade components if Flux is unavailable.

```blade
<flux:button variant="primary"/>
```

### Documentation

If available, use the `search-docs` tool to get the exact documentation and code snippets available for this project.

## Available Components

This is correct as of Boost installation, but there may be additional components within the codebase.

**Full component list:**
accordion, autocomplete, avatar, badge, brand, breadcrumbs, button, calendar, callout, card, chart, checkbox, command, composer, context, date-picker, dropdown, editor, field, file-upload, heading, icon, input, kanban, modal, navbar, otp-input, pagination, pillbox, popover, profile, radio, select, separator, skeleton, slider, switch, table, tabs, text, textarea, time-picker, toast, tooltip

## Common Patterns

### Buttons

```blade
<flux:button variant="primary">Primary</flux:button>
<flux:button variant="secondary">Secondary</flux:button>
<flux:button variant="danger">Delete</flux:button>
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

- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
