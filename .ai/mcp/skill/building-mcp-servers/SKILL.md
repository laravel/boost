---
name: building-mcp-servers
description: >-
  Build MCP servers, tools, resources, and prompts. MUST activate when creating MCP tools,
  resources, or prompts; setting up AI integrations; debugging MCP connections; working with
  routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server,
  or building tools for AI assistants.
---
# Building MCP Servers

## When to Use This Skill

Activate this skill when:
- Creating new MCP tools, resources, or prompts
- Setting up MCP server routes
- Testing MCP functionality
- Debugging MCP connection issues

## Core Patterns

### Overview

MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.

### Registration

MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically, they will be registered using `Mcp::web()` to register an HTTP streaming MCP server.

```php
// routes/ai.php
use Laravel\Mcp\Facades\Mcp;

Mcp::web();
```

## Tools

Tools are functions that the AI can call to perform actions:

```php
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Request;
use Laravel\Mcp\Server\Response;

class MyTool extends Tool
{
    public function handle(Request $request): Response
    {
        // Tool implementation
        return new Response(['result' => 'success']);
    }
}
```

## Testing

Servers are very testable; use the `search-docs` tool to find testing instructions.

## Important Notes

- **Do not run `mcp:start`**. This command hangs waiting for JSON-RPC MCP requests.
- Some MCP clients use Node, which has its own certificate store. If a user tries to connect to their web MCP server locally using HTTPS, it could fail due to this reason. They will need to switch to HTTP during local development.

## Common Pitfalls

- Running `mcp:start` command (it hangs waiting for input)
- Using HTTPS locally with Node-based MCP clients
- Not using `search-docs` for the latest MCP documentation
- Not registering MCP server routes in `routes/ai.php`
- Do not mention ai.php in the booststrap.php file it's already registered.
