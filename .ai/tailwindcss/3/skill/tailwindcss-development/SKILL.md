---
name: tailwindcss-development
description: "Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS."
license: MIT
metadata:
  author: laravel
---
# Tailwind CSS development

## Documentation

Use `search-docs` for detailed Tailwind CSS v3 patterns and documentation.

## Basic usage

- Use Tailwind CSS classes to style HTML. Before introducing new patterns, check and follow the project's existing Tailwind conventions.
- Offer to extract repeated patterns into components that match the project's conventions, such as Blade, JSX, or Vue components.
- Consider class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child elements carefully to reduce repetition, and group elements logically.

## Tailwind CSS v3 specifics

- Always use Tailwind CSS v3 and verify that you use only classes it supports.
- Configure Tailwind in the `tailwind.config.js` file.
- Import Tailwind with the `@tailwind` directives:

<!-- v3 Import Syntax -->
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## Spacing

When listing items, use gap utilities for spacing. Don't use margins.

<!-- Gap Utilities -->
```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## Dark mode

If existing pages and components support dark mode, new pages and components must support it the same way, typically using the `dark:` variant:

<!-- Dark Mode -->
```html
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content adapts to color scheme
</div>
```

## Common patterns

### Flexbox layout

<!-- Flexbox Layout -->
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### Grid layout

<!-- Grid Layout -->
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## Verification

1. Check the visual rendering in the browser
2. Test responsive breakpoints
3. If the project uses dark mode, verify it

## Common pitfalls

- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode
- Not checking existing project conventions before adding new utilities
- Overusing inline styles when Tailwind classes would suffice
