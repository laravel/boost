---
name: mcp-development
description: "Develops MCP servers, tools, resources, and prompts. Activates when creating MCP tools, resources, or prompts; setting up AI integrations; debugging MCP connections; working with routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server, or building tools for AI assistants."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# MCP Development

## Documentation First

**CRITICAL**: Always use `search-docs` BEFORE writing MCP code. The documentation is version-specific, comprehensive, and always up-to-date.

@boostsnippet("Search MCP Documentation", "bash")
# Example searches
search-docs(['mcp tools', 'mcp resources', 'mcp validation'])
@endboostsnippet

## Quick Reference

### Artisan Commands

@boostsnippet("Create MCP Primitives", "bash")
{{ $assist->artisanCommand('make:mcp-tool ToolName') }}
{{ $assist->artisanCommand('make:mcp-resource ResourceName') }}
{{ $assist->artisanCommand('make:mcp-prompt PromptName') }}
{{ $assist->artisanCommand('make:mcp-server ServerName') }}
@endboostsnippet

### Basic Tool Implementation

@boostsnippet("Tool Example", "php")
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MyTool extends Tool
{
    protected string $description = 'Tool description for LLM';

    public function schema(JsonSchema $schema): array
    {
        return [
            'param' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        return Response::text($request->get('param'));
    }
}
@endboostsnippet

### Basic Resource Implementation

@boostsnippet("Resource Example", "php")
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class MyResource extends Resource
{
    protected string $description = 'Resource description';
    protected string $uri = 'file://path/to/resource';
    protected string $mimeType = 'text/markdown';

    public function handle(): Response
    {
        return Response::text($content);
    }
}
@endboostsnippet

### Response Methods

@boostsnippet("Available Responses", "php")
Response::text('Text content');
Response::error('Error message');
Response::structured(['key' => 'value']);
@endboostsnippet

## Testing MCP Primitives

Test tools, resources, and prompts directly on their server:

@boostsnippet("Test MCP Primitives", "php")
// Test a tool
$response = MyServer::tool(MyTool::class, ['param' => 'value']);
$response->assertOk()->assertSee('Expected text');

// Test as authenticated user
$response = MyServer::actingAs($user)->tool(MyTool::class, [...]);

// Available assertions
$response->assertOk();
$response->assertSee('text');
$response->assertHasErrors();
$response->assertHasNoErrors();
$response->assertName('tool-name');
$response->assertSentNotification('event/type', ['data' => 'value']);
@endboostsnippet

### MCP Inspector

Test interactively using the inspector:

@boostsnippet("Launch MCP Inspector", "bash")
{{ $assist->artisanCommand('mcp:inspector mcp/my-server') }}  # Web server
{{ $assist->artisanCommand('mcp:inspector my-server') }}      # Local server
@endboostsnippet

## Available Features

The following features exist—**use `search-docs` for implementation details**:

- **Tools**: `schema()`, validation, annotations (`#[IsReadOnly]`, `#[IsDestructive]`, etc.)
- **Resources**: URI templates (`HasUriTemplate`), Dynamic resources
- **Prompts**: Arguments, multi-message responses
- **All primitives**: Dependency injection, `shouldRegister()`, validation
- **Responses**: Text, error, structured, streaming, metadata
- **Server registration**: Web routes, local routes, OAuth

## Critical Imports

@boostsnippet("Correct Imports", "php")
use Laravel\Mcp\Request;           // NOT Laravel\Mcp\Server\Request
use Laravel\Mcp\Response;          // NOT Laravel\Mcp\Server\Response
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Prompt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
@endboostsnippet

## Common Pitfalls

- **Not using `search-docs` before implementation**
- Wrong imports: `Laravel\Mcp\Server\Request` (wrong) vs `Laravel\Mcp\Request` (correct)
- Forgetting `schema()` method for tools with parameters
- Missing required properties: `$description`, `$uri`, `$mimeType`
- Wrong response pattern: `new Response()` instead of `Response::text()`
- Running `mcp:start` command locally (hangs waiting for stdin)
