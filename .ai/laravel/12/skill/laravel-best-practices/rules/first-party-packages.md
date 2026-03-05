---
title: Package Selection
impact: MEDIUM
tags: architecture, packages, laravel
---

# Package Selection

When adding a package, prefer official Laravel packages first — they integrate more tightly and follow the same conventions. When no first-party option exists, reach for well-maintained third-party packages from trusted ecosystems such as [Spatie](https://spatie.be/open-source), [The PHP League](https://thephpleague.com/), and other reputable maintainers.

Always use the latest stable version of a package and keep dependencies up to date. Outdated packages accumulate security vulnerabilities and drift from the framework's conventions.

## Authentication & Authorization

| Need | Package | Description |
|------|---------|-------------|
| API token auth | Sanctum | Lightweight token-based auth for SPAs, mobile apps, and simple APIs. Issues personal access tokens and manages cookie-based session auth for first-party frontends. |
| OAuth 2.0 server | Passport | Full OAuth 2.0 server built on League OAuth2. Use when you need grant types (authorization code, client credentials, refresh tokens) for third-party API consumers. |
| OAuth social login | Socialite | "Login with Google/GitHub/Facebook" in minutes. Handles the full OAuth redirect flow and returns a normalized user object across 100+ providers. |
| Backend auth scaffolding | Fortify | Headless authentication backend — registration, login, 2FA, email verification, password reset. You build the frontend; Fortify handles the routes and logic. |

## Payments

| Need | Package | Description |
|------|---------|-------------|
| Stripe billing & subscriptions | Cashier (Stripe) | Fluent interface for Stripe subscriptions, one-off charges, invoices, payment methods, and the customer billing portal. Handles webhooks and proration automatically. |
| Paddle billing & subscriptions | Cashier (Paddle) | Same Cashier API but for Paddle. Paddle acts as merchant of record, handling tax compliance and multi-currency — useful when you don't want to manage VAT/GST yourself. |

## Queues, Events & Real-Time

| Need | Package | Description |
|------|---------|-------------|
| Queue dashboard & monitoring | Horizon | Redis queue dashboard with real-time metrics, job throughput, runtime, and failure tracking. Configures queue workers, balancing strategies, and retry policies via code. |
| WebSocket server | Reverb | First-party WebSocket server that integrates with Laravel's broadcasting system. Replaces Pusher for self-hosted real-time events. Scales horizontally with Redis. |

## Search & Feature Flags

| Need | Package | Description |
|------|---------|-------------|
| Full-text search | Scout | Adds full-text search to Eloquent models via driver-based engines (Meilisearch, Typesense, Algolia, database). Automatically syncs model changes to the search index. |
| Feature flags | Pennant | Lightweight feature flag system with class-based definitions, rich scope support (per-user, per-team), and a built-in Blade directive `@feature`. Stores state in database or array driver. |

## Monitoring & Debugging

| Need | Package | Description |
|------|---------|-------------|
| Application performance monitoring | Nightwatch | Production exception tracking, slow route/job/command detection, and occurrence statistics. Sends alerts and groups issues automatically. |
| Real-time metrics dashboard | Pulse | Self-hosted application metrics — slow queries, cache hit rates, queue throughput, exceptions, and custom recorders. Stores data in your own database with configurable retention. |
| Debug assistant (local) | Telescope | Local development debug tool. Records requests, exceptions, queries, jobs, mail, notifications, cache operations, and more. Never run in production. |

## AI & Developer Tools

| Need | Package | Description |
|------|---------|-------------|
| AI integration toolkit | AI SDK | Unified API for interacting with LLMs (Anthropic, OpenAI, etc.) from Laravel. Provides structured output, tool calling, and streaming with a fluent builder interface. |
| AI agent context & MCP tools | Boost | Gives AI coding assistants deep context about your Laravel app — database schema, routes, models, Artisan commands, docs search — via MCP tools and composable guidelines. |
| Model Context Protocol server | MCP | Build custom MCP servers in Laravel. Define tools, resources, and prompts that AI assistants can discover and invoke against your application. |

## Frontend & Routing

| Need | Package | Description |
|------|---------|-------------|
| File-based routing | Folio | Page-based routing — create a Blade file in `resources/views/pages` and it becomes a route. Supports route model binding, middleware, and route parameters via filename conventions. |
| TypeScript route generation | Wayfinder | Generates typed TypeScript functions from your Laravel routes and controllers. Call backend routes from frontend code with full autocompletion and type safety. |
| Live frontend validation | Precognition | Validates form inputs against your backend Form Request rules in real-time as the user types, without submitting the form. Works with Inertia, Alpine, and vanilla JS. |

## Performance & Deployment

| Need | Package | Description |
|------|---------|-------------|
| High-performance app server | Octane | Boots your Laravel app once and keeps it in memory across requests using Swoole, RoadRunner, or FrankenPHP. Dramatically reduces response times for CPU-bound apps. |
| Managed cloud deployment | Cloud | Fully managed deployment platform built by the Laravel team. Auto-scaling compute, managed databases, caches, and object storage — zero server configuration. |

## Testing & Code Quality

| Need | Package | Description |
|------|---------|-------------|
| Browser testing | Dusk | End-to-end browser testing with a real Chrome instance. Test JavaScript-heavy UIs, file uploads, drag-and-drop, and authentication flows that can't be tested with HTTP tests. |
| Code style fixer | Pint | Opinionated PHP code style fixer built on PHP-CS-Fixer. Ships with Laravel preset out of the box. Run once and your entire codebase follows consistent formatting. |

## Development Environment

| Need | Package | Description |
|------|---------|-------------|
| Docker dev environment | Sail | Lightweight Docker Compose setup with PHP, MySQL/Postgres, Redis, Meilisearch, and more. One-command local development without installing anything on your machine. |
| macOS dev environment | Valet | Minimal macOS development environment that runs Nginx in the background. Auto-configures `.test` domains for every project in your sites directory. |
| Vagrant dev environment | Homestead | Pre-packaged Vagrant box with PHP, Nginx, MySQL, Postgres, Redis, and other tools. Useful for teams that need identical environments across different operating systems. |

## CLI & Ops

| Need | Package | Description |
|------|---------|-------------|
| Interactive CLI prompts | Prompts | Beautiful, user-friendly CLI forms — text inputs, selects, multi-selects, confirmations, search, and tables. Used by Artisan and available for your own commands. |
| Remote task runner | Envoy | Define deployment scripts and remote server tasks in a Blade-like syntax. Run SSH commands across multiple servers in sequence or parallel from a single config file. |
| Real-time log viewer | Pail | Tail your Laravel logs directly in the terminal with filtering by level, channel, or keyword. Works across all log channels including stack, daily, and stderr. |
