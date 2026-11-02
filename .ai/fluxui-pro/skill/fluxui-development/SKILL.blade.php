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

This project uses the Pro edition of Flux UI, which includes all free and Pro components and variants.

Flux UI is a component library for Livewire built with Tailwind CSS.

Use a Flux UI component when one exists. If no Flux component fits your needs, fall back to a standard Blade component.

@boostsnippet("Basic Button", "blade")
<flux:button variant="primary">Click me</flux:button>
@endboostsnippet

## Available components (Pro edition)

Available: accordion, autocomplete, avatar, badge, brand, breadcrumbs, button, calendar, callout, card, chart, checkbox, color-picker, command, composer, context, date-picker, dropdown, editor, field, file-upload, heading, icon, input, kanban, modal, navbar, otp-input, pagination, pillbox, popover, profile, progress, radio, select, separator, skeleton, slider, switch, table, tabs, text, textarea, time-picker, timeline, toast, tooltip

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

### Tables

@boostsnippet("Table", "blade")
<flux:table>
    <flux:table.columns>
        <flux:table.cell>Column Name</flux:table.cell>
    </flux:table.columns>
    <flux:table.row>
        <flux:table.cell>Value</flux:table.cell>
    </flux:table.row>
</flux:table>
@endboostsnippet

## Verification

1. Check that the component renders correctly
2. Test interactive states
3. Verify mobile responsiveness

## Common pitfalls

- Not checking whether a Flux component exists before building a custom one
- Forgetting to use the `search-docs` tool for component-specific documentation
- Not following the project's existing Flux patterns
