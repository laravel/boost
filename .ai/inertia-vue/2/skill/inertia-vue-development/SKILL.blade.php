---
name: inertia-vue-development
description: "Develops Inertia.js v2 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Vue Development

Follow the project's existing conventions first: match how current pages, forms, and navigation are already written before applying anything below.

Use `search-docs` for Inertia v2 Vue syntax and APIs.

## Pages

Vue page components live in `{{ $assist->inertia()->pagesDirectory() }}`. Vue components must have a single root element. Server-side patterns (`Inertia::render`, props, middleware) are covered in inertia-laravel guidelines.

## Navigation

- Use `<Link>` from `@inertiajs/vue3` instead of `<a>`; plain anchors cause full page reloads and break SPA behavior.
- `<Link method="post" as="button">` for non-GET actions such as logout; `prefetch` on a link to load the page ahead of the click.
- Use `router.visit` (or `router.post` and friends) only for programmatic navigation that is not a link.

## Forms

@if($assist->inertia()->hasFormComponent())
Prefer `<Form>` for new forms unless the project already uses `useForm` everywhere. It exposes `errors`, `processing`, `progress`, `wasSuccessful`, `recentlySuccessful`, `isDirty`, `reset`, and `clearErrors` through its slot/render props; use `search-docs` for the exact syntax.

@if($assist->inertia()->hasFormComponentResets())
`resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` control automatic resetting; search docs for `form component resetting`.
@else
This Inertia version does not support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on `<Form>` and using them will error. Upgrade to Inertia v2.2.0+ to use them.
@endif

Use `useForm` when you need programmatic control (transforming data, submitting from code) or when the project already follows that convention. Search docs for `useForm helper`.
@else
Inertia v2.0.x has no `<Form>` component (added in v2.1.0). Build forms with `useForm`.
@endif

Always show `errors` per field and disable submit while `processing`. Use `<Form>` or `@submit.prevent`.

## Inertia v2 Features

- **Deferred props**: the prop is `undefined` until loaded, so render a skeleton or empty state first; guard with `v-if`.
- **Polling**: `usePoll(ms)` cleans up on unmount and throttles in inactive tabs. Pass `only` to limit refreshed props, `autoStart: false` for manual `start()`/`stop()`, `keepAlive: true` to keep polling in background tabs.
- **WhenVisible**: loads a prop only when its element scrolls into view; for infinite scroll pass `data` and the next `page` param, plus a fallback, and render it only while a next page exists.

## Common Pitfalls

- Using `<a>` instead of `<Link>`
- Multiple root elements in a page component
- No loading state for deferred props, or reading them while `undefined`
- Submitting a plain form without preventing default (use `<Form>` or the framework prevent-default handler)
- Using `<Form>` or reset props without checking the installed Inertia version
