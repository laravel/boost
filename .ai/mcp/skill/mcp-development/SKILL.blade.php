---
name: mcp-development
description: "Use this skill for Laravel MCP development. Trigger when creating or editing Laravel MCP servers, tools, prompts, resources, resource templates, MCP Apps, authentication, tests, or clients. Covers: make:mcp-* generators, server and primitive registration, current attributes, response shapes, mcp:inspector, direct testing, metadata, icons, authorization, and client APIs. Do not use for non-Laravel MCP projects or generic AI features without MCP."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# MCP Development

Follow the project's existing MCP conventions first (server layout, naming, attribute usage, response style). **Always `search-docs` BEFORE writing MCP code**; it is version-specific, e.g. `mcp tool output schema`, `mcp resource templates`, `mcp client oauth`.

## Scaffolding and Registration

```bash
{{ $assist->artisanCommand('make:mcp-server ServerName') }}
{{ $assist->artisanCommand('make:mcp-tool ToolName') }}
{{ $assist->artisanCommand('make:mcp-resource ResourceName') }}
{{ $assist->artisanCommand('make:mcp-prompt PromptName') }}
{{ $assist->artisanCommand('make:mcp-app-resource AppName') }}
```

- Generators only create classes. Add primitives to the server's `$tools`, `$resources`, `$prompts` arrays and register the server in `routes/ai.php` with `Mcp::web()` or `Mcp::local()`. If the file is missing, run `{{ $assist->artisanCommand('vendor:publish --tag=ai-routes') }}`.
- Configure server identity with `#[Name]`, `#[Version]`, `#[Instructions]`. Use attributes, not legacy `$description`, `$uri`, `$mimeType` properties.

## Primitives

- **Tools**: descriptions are never generated, so always write a `#[Description]` saying when and why to use the tool. Define `schema()` for parameters; add `outputSchema()` only when clients must parse structured content. Validate with `$request->validate()` and actionable messages. Override `#[Name]`/`#[Title]` only when the class-derived value is wrong.
- **Resources**: have no input schema. `#[Uri]` and `#[MimeType]` are optional for static resources. Templates implement `HasUriTemplate`, and URI variables arrive in the `Request`.
- **Prompts**: arguments via `Argument`; may return several responses; `asAssistant()` marks assistant messages.
- **Responses**: use `Response::text()`, `error()`, `structured()`, `image()`, `audio()`, `blob()`, `fromStorage()`, `resourceLink()`, never `new Response()`. Tools may return arrays or yield a `Generator` to stream. `Response::make(...)->withStructuredContent()` combines text with structured data; `Response::notification()` yielded from a `Generator` streams over SSE on web servers. Metadata: `->withMeta()` on content, `Response::make(...)->withMeta()` for result-level, `protected ?array $meta` for primitives.
- **Apps**: link a tool to an app resource with `#[RendersApp(resource: AppResource::class)]`, render with `Response::view(...)`, use `#[AppMeta]` for CSP, permissions, and bundled libraries, and `Visibility::App` / `Visibility::Model` to show app tools to the app, the model, or both. The public docs do not cover Apps, so read the installed `laravel/mcp` source before editing the app SDK integration.

Also available, each with its own docs: tool annotations (`#[IsReadOnly]`, `#[IsDestructive]`, `#[IsIdempotent]`, `#[IsOpenWorld]`), `#[Icon]`, resource annotations (audience, priority, last modified), and dependency injection in `handle()`. Search docs for the one you need.

## Imports

`Laravel\Mcp\Request` and `Laravel\Mcp\Response` (NOT `Laravel\Mcp\Server\Request` / `Response`); `Laravel\Mcp\Server\{Tool,Resource,Prompt}`; `Illuminate\Contracts\JsonSchema\JsonSchema`; attributes live in `Laravel\Mcp\Server\Attributes\*` (`Description`, `Uri`, `MimeType`, ...), the route facade is `Laravel\Mcp\Facades\Mcp`, and the client is `Laravel\Mcp\Client`.

## Testing

- Test directly on the server: `MyServer::tool(...)`, `::prompt(...)`, `::resource(...)`, with `actingAs($user)` for auth. Assert with `assertOk`, `assertSee`, `assertHasErrors`, `assertName`, `assertSentNotification`, etc.
- Interactive: `{{ $assist->artisanCommand('mcp:inspector mcp/my-server') }}` for web servers, `{{ $assist->artisanCommand('mcp:inspector my-server') }}` for local ones. Set an `Authorization` header in the inspector for protected servers.
- Never launch `mcp:start` by hand expecting output; it is an stdio server that waits for a client.

## Authentication and Authorization

- Protect web servers with route middleware (`auth:sanctum`) or Passport OAuth via `Mcp::oauthRoutes()` and `auth:api`.
- Authentication is not authorization: check abilities or policies inside the primitive and return `Response::error('Permission denied.')`. `shouldRegister()` controls discovery only; never rely on it alone for sensitive operations.

## Client

`Client::web($url)` or `Client::local('php', ['artisan', 'mcp:start'])` connects to external servers; named clients use `Mcp::registerClient(...)` / `Mcp::client(...)`. Besides `tools()` / `callTool()`, the client has `prompts()` / `getPrompt()` and `resources()` / `readResource()`; listing methods paginate automatically. The public docs do not cover the client, so read the installed `laravel/mcp` source for OAuth flows and result APIs.

## Pitfalls

- Skipping `search-docs`
- Wrong imports
- Primitives not registered on the server, or server missing from `routes/ai.php`
- Missing `#[Description]`
- Giving a resource an input schema, or forgetting `schema()` on a tool
