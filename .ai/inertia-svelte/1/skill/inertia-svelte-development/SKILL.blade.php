---
name: inertia-svelte-development
description: "Develops Inertia.js v1 Svelte client-side applications. Activates when creating Svelte pages, forms, or navigation; using Link or router; or when user mentions Svelte with Inertia, Svelte pages, Svelte forms, or Svelte navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Svelte Development

Follow the project's existing conventions first: match how current pages, forms, and navigation are already written before applying anything below.

Use `search-docs` for Inertia v1 Svelte syntax and APIs.

## Pages

Svelte page components live in `{{ $assist->inertia()->pagesDirectory() }}`. Server-side patterns (`Inertia::render`, props, middleware) are covered in inertia-laravel guidelines.

## Navigation and Forms

- Use `<Link>` from `@inertiajs/svelte` instead of `<a>`; plain anchors cause full page reloads. Use `method="post"` for actions like logout.
- Use `router.visit` only for programmatic navigation.
- Forms: keep local state, submit with `router.post`, track `processing` yourself via `onFinish`, and prevent default with `on:submit|preventDefault`.

## v1 Limitations

Do not use v2 features in v1 projects: `<Form>`, deferred props, prefetching, polling, `WhenVisible`, or merging props.

## Common Pitfalls

- Using `<a>` instead of `<Link>`
- Using v2 features in a v1 project
- Submitting a form without preventing default
- No loading state during form submission
