---
name: tailwindcss-development
description: "Styles applications using Tailwind CSS v3 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes."
license: MIT
metadata:
  author: laravel
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

- Use Tailwind CSS classes to style HTML. Check and follow existing Tailwind conventions in the project before introducing new patterns.
- Offer to extract repeated patterns into components that match the project's conventions (e.g., Blade, JSX, Vue).
- Consider class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child elements carefully to reduce repetition, and group elements logically.

## Tailwind CSS v3 Specifics

- Always use Tailwind CSS v3 and verify you're using only classes it supports.
- Configuration is done in the `tailwind.config.js` file.
- Import using `@tailwind` directives:

<!-- v3 Import Syntax -->
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## Spacing

When listing items, use gap utilities for spacing; don't use margins.

<!-- Gap Utilities -->
```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## Dark Mode

If existing pages and components support dark mode, new pages and components must support it the same way, typically using the `dark:` variant:

<!-- Dark Mode -->
```html
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content adapts to color scheme
</div>
```

## Common Patterns

### Flexbox Layout

<!-- Flexbox Layout -->
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### Grid Layout

<!-- Grid Layout -->
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## Verification

1. Check browser for visual rendering
2. Test responsive breakpoints
3. Verify dark mode if project uses it

## Common Pitfalls

- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode
- Not checking existing project conventions before adding new utilities
- Overusing inline styles when Tailwind classes would suffice
