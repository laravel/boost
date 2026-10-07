---
name: inertia-vue-development
description: "Develops Inertia.js v2 Svelte client-side applications. Activates when creating Svelte pages, forms, or navigation; using Link, Form, or router; working with deferred props, prefetching, or polling; or when user mentions Svelte with Inertia, Svelte pages, Svelte forms, or Svelte navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Svelte Development

Follow the project's existing conventions first: match how current pages, forms, and navigation are already written before applying anything below.

Use `search-docs` for Inertia v2 Svelte syntax and APIs.

## Pages

Svelte page components live in `{{ $assist->inertia()->pagesDirectory() }}`. Server-side patterns (`Inertia::render`, props, middleware) are covered in inertia-laravel guidelines.

## Navigation

- Use `<Link>` from `@inertiajs/svelte` instead of `<a>`; plain anchors cause full page reloads and break SPA behavior.
- `<Link method="post">` for non-GET actions such as logout; `prefetch` on a link to load the page ahead of the click.
- Use `router.visit` (or `router.post` and friends) only for programmatic navigation that is not a link.

## Forms

@if($assist->inertia()->hasFormComponent())
Prefer `<Form>` for new forms unless the project already uses `useForm` everywhere. It exposes `errors`, `processing`, `progress`, `wasSuccessful`, `recentlySuccessful`, `isDirty`, `reset`, and `clearErrors` through its `let:` bindings; use `search-docs` for the exact syntax.

@if($assist->inertia()->hasFormComponentResets())
`resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` control automatic resetting; search docs for `form component resetting`.
@else
This Inertia version does not support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on `<Form>` and using them will error. Upgrade to Inertia v2.2.0+ to use them.
@endif

Use `useForm` when you need programmatic control (transforming data, submitting from code) or when the project already follows that convention. Search docs for `useForm helper`.
@else
Inertia v2.0.x has no `<Form>` component (added in v2.1.0). Build forms with `useForm`.
@endif

Always show `errors` per field and disable submit while `processing`. Use `<Form>` or `on:submit|preventDefault`.

## Inertia v2 Features

- **Deferred props**: the prop is `undefined` until loaded, so render a skeleton or empty state first; guard with `{#if}`.
- **Polling**: `usePoll(ms)` cleans up on unmount and throttles in inactive tabs. Pass `only` to limit refreshed props, `autoStart: false` for manual `start()`/`stop()`, `keepAlive: true` to keep polling in background tabs.

## Common Pitfalls

- Using `<a>` instead of `<Link>`
- No loading state for deferred props, or reading them while `undefined`
- Submitting a plain form without preventing default (use `<Form>` or the framework prevent-default handler)
- Using `<Form>` or reset props without checking the installed Inertia version
