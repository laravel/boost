---
name: fluxui-development
description: "Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Flux UI development

## Documentation

Use `search-docs` for detailed Flux UI patterns and documentation.

## Basic usage

This project uses the free edition of Flux UI, which includes all free components and variants but not Pro components.

Flux UI is a component library for Livewire built with Tailwind CSS.

Use a Flux UI component when one exists. If no Flux component fits your needs, fall back to a standard Blade component.

@boostsnippet("Basic Button", "blade")
<flux:button variant="primary">Click me</flux:button>
@endboostsnippet

## Available components (free edition)

Available: avatar, badge, brand, breadcrumbs, button, callout, card, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, pagination, profile, progress, radio, select, separator, skeleton, switch, table, text, textarea, toast, tooltip

## Icons

Flux includes [Heroicons](https://heroicons.com/) as its default icon set. Look up exact icon names on the Heroicons site. Do not guess or invent icon names.

@boostsnippet("Icon Button", "blade")
<flux:button icon="arrow-down-tray">Export</flux:button>
@endboostsnippet

For an icon that Heroicons lacks, use [Lucide](https://lucide.dev/). Import the icons you need with the Artisan command:

```bash
{{ $assist->artisanCommand('flux:icon crown grip-vertical github') }}
```

## Common patterns

### Form fields

@boostsnippet("Form Field", "blade")
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
@endboostsnippet

### Modals

@boostsnippet("Modal", "blade")
<flux:modal wire:model="showModal">
    <flux:heading>Title</flux:heading>
    <p>Content</p>
</flux:modal>
@endboostsnippet

## Verification

1. Check that the component renders correctly
2. Test interactive states
3. Verify mobile responsiveness

## Common pitfalls

- Trying to use Pro-only components in the free edition
- Not checking whether a Flux component exists before building a custom one
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following the project's existing Flux patterns
