---
name: mcp-development
description: >-
  Develops MCP servers, tools, resources, and prompts. Activates when creating MCP tools,
  resources, or prompts; setting up AI integrations; debugging MCP connections; working with
  routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server,
  or building tools for AI assistants.
---
# MCP Development

## When to Apply

- Creating MCP tools, resources, or prompts
- Setting up MCP server routes
- Debugging MCP connection issues

## Core Pattern

Register MCP servers in `routes/ai.php`:

```php
use Laravel\Mcp\Facades\Mcp;

Mcp::web();
```

Use `search-docs` with "laravel mcp" for detailed documentation.

## Creating MCP Primitives

Create MCP tools, resources, prompts, and servers using artisan commands:

```bash
{{ $assist->artisanCommand('make:mcp-tool ToolName') }}        # Create a tool
{{ $assist->artisanCommand('make:mcp-resource ResourceName') }} # Create a resource
{{ $assist->artisanCommand('make:mcp-prompt PromptName') }}    # Create a prompt
{{ $assist->artisanCommand('make:mcp-server ServerName') }}    # Create a server
```

After creating primitives, register them in your server's `$tools`, `$resources`, or `$prompts` properties.

## Tools

```php
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Request;
use Laravel\Mcp\Server\Response;

class MyTool extends Tool
{
    public function handle(Request $request): Response
    {
        return new Response(['result' => 'success']);
    }
}
```

## Verification

1. Check `routes/ai.php` for proper registration
2. Test tool via MCP client
3. Use `search-docs` with "mcp testing" for test patterns

## Common Pitfalls

- Running `mcp:start` command (it hangs waiting for input)
- Using HTTPS locally with Node-based MCP clients
- Not using `search-docs` for the latest MCP documentation
- Not registering MCP server routes in `routes/ai.php`
- Do not mention ai.php in the bootstrap.php file; it's already registered.
