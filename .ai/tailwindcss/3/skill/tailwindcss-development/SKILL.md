---
name: tailwindcss-development
description: >-
  Styles applications using Tailwind CSS v3 utilities. Activates when adding styles, restyling components,
  working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors,
  typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle,
  hero section, cards, buttons, or any visual/UI changes.
---
# Tailwind CSS Development

## When to Apply

Activate this skill when:
- Adding styles to components or pages
- Working with responsive design
- Implementing dark mode
- Extracting repeated patterns into components
- Debugging spacing or layout issues

## Documentation

Use `search-docs` for detailed Tailwind CSS v3 patterns and documentation.

## Basic Usage

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.

## Tailwind CSS v3 Specifics

- Always use Tailwind CSS v3; verify you're using only classes supported by this version.
- Configuration is done in `tailwind.config.js` file.
- Import using `@tailwind` directives:

<code-snippet name="v3 Import Syntax" lang="css">
@tailwind base;
@tailwind components;
@tailwind utilities;
</code-snippet>

## Spacing

When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Gap Utilities" lang="html">
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
</code-snippet>

## Dark Mode

If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:` variant:

<code-snippet name="Dark Mode" lang="html">
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content adapts to color scheme
</div>
</code-snippet>

## Common Patterns

### Flexbox Layout

<code-snippet name="Flexbox Layout" lang="html">
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
</code-snippet>

### Grid Layout

<code-snippet name="Grid Layout" lang="html">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
</code-snippet>

## Verification

1. Check browser for visual rendering
2. Test responsive breakpoints
3. Verify dark mode if project uses it

## Common Pitfalls

- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode
- Not checking existing project conventions before adding new utilities
- Overusing inline styles when Tailwind classes would suffice
