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
# MCP development

## Documentation first

**CRITICAL**: Always use `search-docs` BEFORE writing MCP code. The documentation is version-specific and up to date.

@boostsnippet("Search MCP Documentation", "text")
# Example searches
search-docs(['mcp tool output schema', 'mcp resource templates', 'mcp client oauth'])
@endboostsnippet

## Quick reference

### Artisan commands

Create MCP servers and primitives:
```bash
{{ $assist->artisanCommand('make:mcp-server ServerName') }}
{{ $assist->artisanCommand('make:mcp-tool ToolName') }}
{{ $assist->artisanCommand('make:mcp-resource ResourceName') }}
{{ $assist->artisanCommand('make:mcp-prompt PromptName') }}
{{ $assist->artisanCommand('make:mcp-app-resource AppName') }}
```

If `routes/ai.php` does not exist, publish it with `{{ $assist->artisanCommand('vendor:publish --tag=ai-routes') }}`.

### Registration

Generators create the classes but do not register them. Register each primitive on a server, and register the server in `routes/ai.php`:

@boostsnippet("Register MCP Servers", "php")
use App\Mcp\Servers\MyServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/my-server', MyServer::class);
Mcp::local('my-server', MyServer::class);
@endboostsnippet

Add tool, resource, and prompt classes to the server's `$tools`, `$resources`, and `$prompts` arrays. Use the `#[Name]`, `#[Version]`, and `#[Instructions]` attributes to configure server identity and instructions.

### Basic tool implementation

@boostsnippet("Tool Example", "php")
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Describe clearly when and why the model should use this tool.')]
class MyTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'param' => $schema->string()
                ->description('The value to process.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        return Response::text($request->get('param'));
    }
}
@endboostsnippet

Laravel MCP does not generate descriptions. Always add a meaningful `#[Description]`. Use `#[Name]` and `#[Title]` only to override the values derived from the class name. If the tool returns structured content that clients need to parse, define `outputSchema(JsonSchema $schema): array`. Validate complex requirements with `$request->validate()` and actionable custom error messages.

### Basic resource implementation

@boostsnippet("Resource Example", "php")
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('Provides the project guidelines.')]
#[Uri('file://project/guidelines')]
#[MimeType('text/markdown')]
class MyResource extends Resource
{
    public function handle(): Response
    {
        return Response::text('# Project Guidelines');
    }
}
@endboostsnippet

`#[Uri]` and `#[MimeType]` are optional for static resources. If you omit them, Laravel derives a URI and defaults the MIME type to `text/plain`. Resources do not define input schemas or arguments. Laravel merges URI-template variables into the `Request`. For resource templates, implement `Laravel\Mcp\Server\Contracts\HasUriTemplate` and return a `Laravel\Mcp\Support\UriTemplate` from `uriTemplate()`.

### Prompt guidance

Prompts define arguments with `Laravel\Mcp\Server\Prompts\Argument`, may validate their request, and may return one response or an iterable of responses. Use `asAssistant()` to mark assistant messages.

### Common response shapes

@boostsnippet("MCP Responses", "php")
Response::text('Text content');
Response::error('Error message');
Response::structured(['key' => 'value']);
Response::image($bytes, 'image/png');
Response::audio($bytes, 'audio/mp3');
Response::fromStorage('path/file.png', disk: 's3');
Response::resourceLink(uri: 'file:///report.json', name: 'report');
Response::blob($bytes);
Response::view('mcp.dashboard', $data);
@endboostsnippet

Tools may return an array of responses. To stream, return a `Generator` that yields `Response::notification(...)` and content responses. Web servers send the yielded messages over SSE. For custom text plus structured data, use `Response::make(Response::text(...))->withStructuredContent(...)`.

For content metadata, call `->withMeta()` directly on a content response. For result-level metadata, use `Response::make(...)->withMeta(...)`. To configure a primitive's `_meta`, set the `protected ?array $meta` property.

## MCP Apps

Generate apps with `make:mcp-app-resource`. Register the generated app resource in the server's `$resources` array, and render it with `Response::view(...)`. Link a tool to an app resource with `#[RendersApp(resource: AppResource::class)]`. Use `#[AppMeta]` for CSP, permissions, and bundled libraries. Use `Visibility::App` and `Visibility::Model` to control whether app tools are visible to the app, the model, or both. Before editing the app SDK integration, use `search-docs`.

## Testing MCP primitives

Test tools, resources, and prompts directly on their server:

@boostsnippet("Test MCP Primitives", "php")
// Test a tool
$response = MyServer::tool(MyTool::class, ['param' => 'value']);
$response->assertOk()->assertSee('Expected text');

// Prompts and resources use the same direct testing pattern
$response = MyServer::prompt(MyPrompt::class, ['tone' => 'concise']);
$response = MyServer::resource(MyResource::class);

// Test as authenticated user
$response = MyServer::actingAs($user)->tool(MyTool::class, [...]);

// Available assertions
$response->assertOk();
$response->assertSee('text');
$response->assertHasErrors();
$response->assertHasNoErrors();
$response->assertName('tool-name');
$response->assertTitle('Tool Title');
$response->assertDescription('Tool description');
$response->assertSentNotification('event/type', ['data' => 'value']);
$response->assertNotificationCount(1);
@endboostsnippet

Use `$response->dump()` or `$response->dd()` while debugging.

### MCP Inspector

Test the server interactively with the inspector:

```bash
# Mcp::web('/mcp/my-server', MyServer::class)
{{ $assist->artisanCommand('mcp:inspector mcp/my-server') }}

# Mcp::local('my-server', MyServer::class)
{{ $assist->artisanCommand('mcp:inspector my-server') }}
```

For authenticated web servers, configure headers such as `Authorization: Bearer ...` in the inspector.

## Authentication and authorization

- Protect web servers with normal route middleware such as `auth:sanctum`, or configure Passport OAuth 2.1 with `Mcp::oauthRoutes()` and `auth:api`.
- Route authentication and primitive authorization are separate. Check abilities or policies in the primitive. If the check fails, return `Response::error('Permission denied.')`.
- `shouldRegister(Request $request)` controls discovery and availability. For sensitive operations, do not make it the only authorization check.

## MCP client

Laravel MCP includes a client for connecting to external servers:

@boostsnippet("MCP Client", "php")
use Laravel\Mcp\Client;

$client = Client::web('https://mcp.example.com')
    ->withToken($token)
    ->withTimeout(30);

$client->connect();
$tools = $client->tools();
$result = $client->callTool('tool-name', ['key' => 'value']);
$client->disconnect();
@endboostsnippet

For a local stdio server, use `Client::local('php', ['artisan', 'mcp:start'])`. To use named clients, register them with `Mcp::registerClient(...)` and resolve them with `Mcp::client(...)`. The client also provides `prompts()`, `getPrompt()`, `resources()`, and `readResource()`. The listing methods paginate automatically. Use `search-docs` for OAuth flows and result object APIs.

## Available features

The following features exist. **Use `search-docs` for implementation details**:

- **Tools**: `schema()`, `outputSchema()`, validation, and annotations (`#[IsReadOnly]`, `#[IsDestructive]`, `#[IsIdempotent]`, `#[IsOpenWorld]`)
- **Resources**: URI templates (`HasUriTemplate`), resource links, blobs, audience, priority, and last-modified annotations
- **Prompts**: Arguments, validation, and multi-message responses
- **All primitives**: Dependency injection and `shouldRegister(Request $request)`
- **Apps**: App resources, `#[RendersApp]`, visibility, `#[AppMeta]`, and the app SDK
- **Presentation**: Repeatable `#[Icon]` attributes and runtime `icons()`
- **Servers and clients**: Web and local transports, OAuth, named clients, and remote primitive invocation

## Critical imports

@boostsnippet("Correct Imports", "php")
use Laravel\Mcp\Request;           // NOT Laravel\Mcp\Server\Request
use Laravel\Mcp\Response;          // NOT Laravel\Mcp\Server\Response
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Prompt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
@endboostsnippet

## Common pitfalls

- **Not using `search-docs` before implementation**
- Wrong imports: `Laravel\Mcp\Server\Request` (wrong) vs `Laravel\Mcp\Request` (correct)
- Forgetting to register primitives on the server or the server in `routes/ai.php`
- Using legacy `$description`, `$uri`, or `$mimeType` properties instead of attributes
- Omitting a meaningful `#[Description]`. Laravel MCP does not generate descriptions
- Forgetting `schema()` for tool parameters or using a resource input schema (resources have none)
- Wrong response pattern: `new Response()` instead of `Response::text()`
- Manually launching `mcp:start` and expecting interactive output. `mcp:start` is a stdio server that waits for an MCP client
