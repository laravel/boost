---
name: styling-with-tailwind
description: Styling applications with Tailwind CSS. Use when applying utility classes, creating responsive designs, customizing themes, or building component patterns.
---

# Styling with Tailwind CSS

## When to use this skill

Use this skill when the user asks about:
- Applying Tailwind utility classes
- Building responsive layouts
- Creating dark mode themes
- Customizing Tailwind configuration
- Component patterns and best practices

## Core Concepts

Tailwind uses utility classes for styling:

```html
<!-- Traditional CSS approach -->
<div class="card">Card content</div>

<!-- Tailwind approach -->
<div class="bg-white rounded-lg shadow-md p-6">Card content</div>
```

## Layout

### Flexbox

```html
<!-- Horizontal centering -->
<div class="flex justify-center">Centered</div>

<!-- Vertical centering -->
<div class="flex items-center h-screen">Vertically centered</div>

<!-- Both -->
<div class="flex items-center justify-center h-screen">Centered both ways</div>

<!-- Space between -->
<div class="flex justify-between">
    <span>Left</span>
    <span>Right</span>
</div>

<!-- Gap between items -->
<div class="flex gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- Flex direction -->
<div class="flex flex-col">Stacked vertically</div>
<div class="flex flex-row">Side by side</div>
```

### Grid

```html
<!-- Basic grid -->
<div class="grid grid-cols-3 gap-4">
    <div>1</div>
    <div>2</div>
    <div>3</div>
</div>

<!-- Responsive grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Items -->
</div>

<!-- Spanning columns -->
<div class="grid grid-cols-4 gap-4">
    <div class="col-span-2">Spans 2 columns</div>
    <div>Single</div>
    <div>Single</div>
</div>
```

## Responsive Design

Breakpoints: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px), `2xl` (1536px)

```html
<!-- Mobile-first approach -->
<div class="text-sm md:text-base lg:text-lg">
    Responsive text
</div>

<!-- Hide/show at breakpoints -->
<div class="hidden md:block">Only visible on md and up</div>
<div class="block md:hidden">Only visible below md</div>

<!-- Responsive padding -->
<div class="p-4 md:p-6 lg:p-8">Responsive padding</div>

<!-- Responsive grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Cards -->
</div>
```

## Spacing

### Padding and Margin

```html
<!-- Padding -->
<div class="p-4">All sides</div>
<div class="px-4">Horizontal</div>
<div class="py-4">Vertical</div>
<div class="pt-4">Top only</div>
<div class="pl-4">Left only</div>

<!-- Margin -->
<div class="m-4">All sides</div>
<div class="mx-auto">Center horizontally</div>
<div class="my-4">Vertical</div>
<div class="mt-4">Top only</div>
<div class="-mt-4">Negative margin</div>

<!-- Space between children -->
<div class="space-y-4">
    <div>Child 1</div>
    <div>Child 2</div>
</div>
```

## Typography

```html
<!-- Font size -->
<p class="text-xs">Extra small</p>
<p class="text-sm">Small</p>
<p class="text-base">Base</p>
<p class="text-lg">Large</p>
<p class="text-xl">Extra large</p>
<p class="text-2xl">2XL</p>

<!-- Font weight -->
<p class="font-normal">Normal</p>
<p class="font-medium">Medium</p>
<p class="font-semibold">Semibold</p>
<p class="font-bold">Bold</p>

<!-- Text color -->
<p class="text-gray-500">Gray text</p>
<p class="text-blue-600">Blue text</p>
<p class="text-red-500">Red text</p>

<!-- Text alignment -->
<p class="text-left">Left</p>
<p class="text-center">Center</p>
<p class="text-right">Right</p>

<!-- Line height -->
<p class="leading-tight">Tight</p>
<p class="leading-normal">Normal</p>
<p class="leading-loose">Loose</p>
```

## Colors

```html
<!-- Background -->
<div class="bg-white">White</div>
<div class="bg-gray-100">Light gray</div>
<div class="bg-blue-500">Blue</div>
<div class="bg-gradient-to-r from-blue-500 to-purple-500">Gradient</div>

<!-- Text color -->
<span class="text-gray-700">Dark gray text</span>

<!-- Border color -->
<div class="border border-gray-300">Gray border</div>

<!-- Opacity -->
<div class="bg-black bg-opacity-50">50% opacity</div>
<div class="bg-black/50">Same thing (shorthand)</div>
```

## Borders and Shadows

```html
<!-- Border width -->
<div class="border">1px border</div>
<div class="border-2">2px border</div>
<div class="border-t">Top only</div>

<!-- Border radius -->
<div class="rounded">Small radius</div>
<div class="rounded-md">Medium</div>
<div class="rounded-lg">Large</div>
<div class="rounded-full">Full (circle/pill)</div>

<!-- Shadow -->
<div class="shadow">Small shadow</div>
<div class="shadow-md">Medium</div>
<div class="shadow-lg">Large</div>
<div class="shadow-xl">Extra large</div>
```

## Interactive States

```html
<!-- Hover -->
<button class="bg-blue-500 hover:bg-blue-600">Hover me</button>

<!-- Focus -->
<input class="border focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />

<!-- Active -->
<button class="bg-blue-500 active:bg-blue-700">Click me</button>

<!-- Disabled -->
<button class="bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
    Disabled
</button>

<!-- Group hover -->
<div class="group">
    <span class="group-hover:text-blue-500">Changes on parent hover</span>
</div>
```

## Dark Mode

```html
<!-- Dark mode variants -->
<div class="bg-white dark:bg-gray-800">
    <p class="text-gray-900 dark:text-white">Adapts to theme</p>
</div>

<!-- Toggle in tailwind.config.js -->
// darkMode: 'class' (manual) or 'media' (system preference)
```

## Common Components

### Card

```html
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold mb-2">Card Title</h3>
    <p class="text-gray-600">Card content goes here.</p>
</div>
```

### Button

```html
<button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
    Button
</button>
```

### Input

```html
<input
    type="text"
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-colors"
    placeholder="Enter text..."
/>
```

### Badge

```html
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
    Badge
</span>
```

### Alert

```html
<div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
    <p class="font-medium">Warning</p>
    <p>Something needs attention.</p>
</div>
```

## Customization

### tailwind.config.js

```js
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#3490dc',
                secondary: '#ffed4a',
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
            spacing: {
                '72': '18rem',
                '84': '21rem',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

## Best Practices

1. **Mobile-first** - Start with mobile styles, add breakpoints
2. **Extract components** - Use `@apply` sparingly for repeated patterns
3. **Use design tokens** - Customize theme for brand colors
4. **Consistent spacing** - Stick to the spacing scale
5. **Group related classes** - Layout, then spacing, then visual
6. **Purge unused CSS** - Configure content paths correctly
