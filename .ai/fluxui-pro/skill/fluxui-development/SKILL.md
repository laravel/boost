---
name: fluxui-development
description: >-
  Develops UIs with Flux UI Pro components. Activates when creating buttons, forms, modals,
  inputs, tables, charts, date pickers, or UI components; replacing HTML elements with Flux;
  working with flux: components; or when the user mentions Flux, component library, UI components,
  form fields, or asks about available Flux components.
---
# Flux UI Development

## When to Apply

Activate this skill when:

- Creating new UI components or pages
- Working with forms, modals, or interactive elements
- Styling components with Flux UI patterns
- Checking available Flux components

## Documentation

Use `search-docs` for detailed Flux UI patterns and documentation.

## Basic Usage

This project uses the Pro version of Flux UI, which includes all free and Pro components and variants.

Flux UI is a component library for Livewire built with Tailwind CSS. It provides components that are easy to use and customize.

Use Flux UI components when available. Fall back to standard Blade components when no Flux component exists for your needs.

<code-snippet name="Basic Button" lang="blade">
<flux:button variant="primary">Click me</flux:button>
</code-snippet>

## Available Components (Pro Edition)

Available: accordion, autocomplete, avatar, badge, brand, breadcrumbs, button, calendar, callout, card, chart, checkbox, command, composer, context, date-picker, dropdown, editor, field, file-upload, heading, icon, input, kanban, modal, navbar, otp-input, pagination, pillbox, popover, profile, radio, select, separator, skeleton, slider, switch, table, tabs, text, textarea, time-picker, toast, tooltip

## Common Patterns

### Form Fields

<code-snippet name="Form Field" lang="blade">
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
</code-snippet>

### Tables

<code-snippet name="Table" lang="blade">
<flux:table>
    <flux:table.head>
        <flux:table.row>
            <flux:table.cell>Name</flux:table.cell>
        </flux:table.row>
    </flux:table.head>
</flux:table>
</code-snippet>

## Verification

1. Check component renders correctly
2. Test interactive states
3. Verify mobile responsiveness

## Common Pitfalls

- Not checking if a Flux component exists before creating custom implementations
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following existing project patterns for Flux usage
