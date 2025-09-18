## Laravel MCP
- MCP, Model Context Protocol, is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically you should start with `Mcp::web()` to register a HTTP streaming MCP server.
- Servers are very testable - use the `search-docs` tool to find testing instructions.
- Do not run `mcp:start`. This command hangs waiting for JSON RPC MCP requests.
