# Fix ResponseFactory::map() Method Not Found Error in CallToolWithExecutor

## Description

This PR fixes a critical bug in `CallToolWithExecutor` where the code attempts to call `->map()` directly on a `ResponseFactory` object, causing a `BadMethodCallException` when executing MCP tools. The fix ensures that `->responses()` is called first to get the underlying Collection before using `->map()` and `->contains()`.

## Problem

When executing MCP tools via Laravel Boost, the server crashes with:
```
BadMethodCallException: Method Laravel\Mcp\ResponseFactory::map does not exist.
```

This occurs because `ResponseFactory` doesn't have a `map()` method. According to the `ResponseFactory` API in `laravel/mcp`, you must first call `->responses()` to get the underlying `Collection`, then call `->map()` on that collection.

### Root Cause

In `src/Mcp/Methods/CallToolWithExecutor.php` at line 62, the code was calling `->map()` directly on a `ResponseFactory` object:

```php
// Before (broken)
return $this->toJsonRpcResponse($request, $response, fn ($responses): array => [
    'content' => $responses->map(fn ($response) => $response->content()->toTool($tool))->all(),
    'isError' => $responses->contains(fn ($response) => $response->isError()),
]);
```

However, the callback receives a `ResponseFactory` object (not a Collection), which doesn't have `map()` or `contains()` methods.

## Solution

The fix updates the callback to call `->responses()` first to get the Collection, then call `->map()` and `->contains()` on that collection:

```php
// After (fixed)
return $this->toJsonRpcResponse($request, $response, fn ($responseFactory): array => [
    'content' => $responseFactory->responses()->map(fn ($response) => $response->content()->toTool($tool))->all(),
    'isError' => $responseFactory->responses()->contains(fn ($response) => $response->isError()),
]);
```

## Changes Made

- **File**: `src/Mcp/Methods/CallToolWithExecutor.php`
  - Updated the callback parameter name from `$responses` to `$responseFactory` to better reflect the actual type
  - Added `->responses()` calls before `->map()` and `->contains()` to access the underlying Collection

## Testing

Comprehensive test coverage has been added in `tests/Feature/Mcp/CallToolWithExecutorTest.php`:

1. ✅ **`handles tool execution with ResponseFactory correctly`** - Verifies the fix works and no `BadMethodCallException` is thrown
2. ✅ **`handles tool execution error correctly`** - Ensures exceptions are handled gracefully
3. ✅ **`handles ResponseFactory with multiple responses correctly`** - Specifically tests that `->responses()->map()` works correctly
4. ✅ **`throws JsonRpcException when tool name is missing`** - Tests validation
5. ✅ **`throws JsonRpcException when tool is not found`** - Tests validation

All 5 tests pass with 12 assertions.

## Benefit to End Users

- **Fixes MCP tool execution**: Users can now successfully execute MCP tools (e.g., `get-config`, `application-info`, `tinker`) without the server crashing
- **Prevents connection failures**: The MCP server no longer crashes and closes connections when tools are executed
- **Improves reliability**: The MCP server remains stable and functional for tool execution
- **Better error handling**: Tool execution errors are now properly handled and returned as JSON-RPC responses instead of causing server crashes

## Why This Doesn't Break Existing Features

- **No API changes**: The fix only corrects the internal implementation to use the correct `ResponseFactory` API
- **Backward compatible**: The method signature and return types remain unchanged
- **Same behavior**: The functionality remains the same, just using the correct API calls
- **No breaking changes**: All existing code that uses `CallToolWithExecutor` will continue to work, but now correctly

## How This Makes Building Web Applications Easier

- **Enables AI-assisted development**: Laravel Boost's MCP integration allows AI assistants (like Cursor, Claude, etc.) to interact with Laravel applications, making development faster and more efficient
- **Improves developer experience**: Developers can now use MCP tools to query configuration, execute tinker commands, check routes, and more without manual intervention
- **Reduces debugging time**: When MCP tools work correctly, developers can quickly get information about their application state through AI assistants
- **Enhances productivity**: The ability to execute tools programmatically through MCP enables more sophisticated AI-assisted workflows

## Related Issues

This fixes the bug described in the issue where MCP tools fail to execute with `BadMethodCallException: Method Laravel\Mcp\ResponseFactory::map does not exist.`

## Testing Instructions

1. Install Laravel Boost: `composer require laravel/boost --dev`
2. Run the test suite: `./vendor/bin/pest tests/Feature/Mcp/CallToolWithExecutorTest.php`
3. All tests should pass

## Checklist

- [x] Tests added/updated
- [x] Code follows Laravel coding standards
- [x] No breaking changes
- [x] Documentation updated (if needed)
- [x] All tests pass

