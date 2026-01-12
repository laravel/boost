---
name: building-mcp-servers
description: Building Model Context Protocol servers with Laravel. Use when creating MCP tools, resources, or prompts for AI assistants.
---

# Building MCP Servers

## When to use this skill

Use this skill when the user asks about:
- Creating MCP tools for AI assistants
- Building MCP resources
- Implementing MCP prompts
- Integrating with Claude, Cursor, or other MCP clients

## What is MCP?

Model Context Protocol (MCP) is a standard for AI assistants to interact with external tools and data. Laravel Boost provides an MCP server implementation.

## Creating Tools

Tools are actions the AI can execute:

```php
// app/Mcp/Tools/CreateUser.php
namespace App\Mcp\Tools;

use Laravel\Boost\Mcp\Tool;
use Laravel\Boost\Mcp\ToolResult;

class CreateUser extends Tool
{
    public function name(): string
    {
        return 'create_user';
    }

    public function description(): string
    {
        return 'Create a new user in the application';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The user\'s full name',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'The user\'s email address',
                ],
            ],
            'required' => ['name', 'email'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $user = User::create([
            'name' => $arguments['name'],
            'email' => $arguments['email'],
            'password' => bcrypt(Str::random(16)),
        ]);

        return ToolResult::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
```

## Tool Results

Return structured results:

```php
// Success with data
return ToolResult::success([
    'message' => 'User created',
    'user' => $user->toArray(),
]);

// Success with text
return ToolResult::text('Operation completed successfully');

// Error
return ToolResult::error('User not found');

// With metadata
return ToolResult::success($data)
    ->withMeta(['cached' => true]);
```

## Creating Resources

Resources provide data the AI can read:

```php
// app/Mcp/Resources/UserList.php
namespace App\Mcp\Resources;

use Laravel\Boost\Mcp\Resource;

class UserList extends Resource
{
    public function uri(): string
    {
        return 'users://list';
    }

    public function name(): string
    {
        return 'User List';
    }

    public function description(): string
    {
        return 'List of all users in the system';
    }

    public function mimeType(): string
    {
        return 'application/json';
    }

    public function read(): string
    {
        $users = User::select(['id', 'name', 'email', 'created_at'])
            ->orderBy('name')
            ->get();

        return json_encode($users, JSON_PRETTY_PRINT);
    }
}
```

## Dynamic Resources

Resources with parameters:

```php
class UserProfile extends Resource
{
    public function uri(): string
    {
        return 'users://{id}/profile';
    }

    public function uriTemplate(): string
    {
        return 'users://{id}/profile';
    }

    public function read(array $params = []): string
    {
        $user = User::findOrFail($params['id']);

        return json_encode([
            'name' => $user->name,
            'email' => $user->email,
            'posts_count' => $user->posts()->count(),
        ]);
    }
}
```

## Creating Prompts

Prompts provide context templates:

```php
// app/Mcp/Prompts/CodeReview.php
namespace App\Mcp\Prompts;

use Laravel\Boost\Mcp\Prompt;

class CodeReview extends Prompt
{
    public function name(): string
    {
        return 'code_review';
    }

    public function description(): string
    {
        return 'Prompt for reviewing Laravel code';
    }

    public function arguments(): array
    {
        return [
            [
                'name' => 'file_path',
                'description' => 'Path to the file to review',
                'required' => true,
            ],
        ];
    }

    public function generate(array $arguments): array
    {
        $content = file_get_contents($arguments['file_path']);

        return [
            [
                'role' => 'user',
                'content' => "Please review this Laravel code:\n\n```php\n{$content}\n```",
            ],
        ];
    }
}
```

## Registering Components

Register in the MCP server:

```php
// app/Providers/McpServiceProvider.php
use Laravel\Boost\Mcp\Boost;

public function boot(): void
{
    Boost::tool(CreateUser::class);
    Boost::tool(QueryDatabase::class);

    Boost::resource(UserList::class);
    Boost::resource(UserProfile::class);

    Boost::prompt(CodeReview::class);
}
```

## Input Validation

Validate tool inputs:

```php
public function execute(array $arguments): ToolResult
{
    $validator = Validator::make($arguments, [
        'email' => 'required|email|unique:users',
        'name' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return ToolResult::error(
            $validator->errors()->first()
        );
    }

    // Process valid input...
}
```

## Error Handling

Handle errors gracefully:

```php
public function execute(array $arguments): ToolResult
{
    try {
        $user = User::findOrFail($arguments['id']);
        $user->delete();

        return ToolResult::success(['deleted' => true]);
    } catch (ModelNotFoundException $e) {
        return ToolResult::error('User not found');
    } catch (\Exception $e) {
        report($e);
        return ToolResult::error('An error occurred');
    }
}
```

## Testing Tools

```php
use App\Mcp\Tools\CreateUser;

test('create user tool works', function () {
    $tool = new CreateUser();

    $result = $tool->execute([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($result->isSuccess())->toBeTrue();
    expect($result->data()['name'])->toBe('John Doe');

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});
```

## Client Configuration

Configure MCP clients to connect:

```json
// .mcp.json (for Claude Code)
{
    "servers": {
        "laravel-app": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

## Best Practices

1. **Descriptive names** - Clear tool/resource names
2. **Detailed schemas** - Document all parameters
3. **Handle errors** - Return meaningful error messages
4. **Validate input** - Check all arguments
5. **Limit scope** - Tools should do one thing well
6. **Test thoroughly** - Cover success and failure cases
7. **Secure access** - Consider authentication/authorization
