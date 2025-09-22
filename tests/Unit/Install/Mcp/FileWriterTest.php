<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Mcp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Boost\Install\Mcp\FileWriter;
use Mockery;
use ReflectionClass;

beforeEach(function (): void {
    Mockery::close();
});

test('constructor sets file path', function (): void {
    $writer = new FileWriter('/path/to/mcp.json');
    expect($writer)->toBeInstanceOf(FileWriter::class);
});

test('configKey method returns self for chaining', function (): void {
    $writer = new FileWriter('/path/to/mcp.json');
    $result = $writer->configKey('customKey');

    expect($result)->toBe($writer);
});

test('addServer method returns self for chaining', function (): void {
    $writer = new FileWriter('/path/to/mcp.json');
    $result = $writer
        ->configKey('servers')
        ->addServer('test', 'php', ['artisan'], ['ENV' => 'value']);

    expect($result)->toBe($writer);
});

test('save method returns boolean', function (): void {
    mockFileOperations();
    $writer = new FileWriter('/path/to/mcp.json');
    $result = $writer->save();

    expect($result)->toBe(true);
});

test('written data is correct for brand new file', function (string $configKey, array $servers, string $expectedJson): void {
    $writtenPath = '';
    $writtenContent = '';
    mockFileOperations(capturedPath: $writtenPath, capturedContent: $writtenContent);

    $writer = (new FileWriter('/path/to/mcp.json'))
        ->configKey($configKey);

    foreach ($servers as $serverKey => $serverConfig) {
        $writer->addServer(
            $serverKey,
            $serverConfig['command'],
            $serverConfig['args'] ?? [],
            $serverConfig['env'] ?? []
        );
    }

    $result = $writer->save();

    $simpleContents = Str::of($writtenContent)->replaceMatches('/\s+/', '');
    expect($result)->toBe(true);
    expect($simpleContents)->toEqual($expectedJson);
})->with(newFileServerConfigurations());

test('updates existing plain JSON file using simple method', function (): void {
    $writtenPath = '';
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-plain.json'),
        capturedPath: $writtenPath,
        capturedContent: $writtenContent
    );

    // Need to mock File::size for fileEmpty check
    File::shouldReceive('size')->andReturn(100);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->configKey('servers')
        ->addServer('new-server', 'npm', ['start'])
        ->save();

    expect($result)->toBeTrue();

    $decoded = json_decode((string) $writtenContent, true);
    expect($decoded)->toHaveKey('existing');
    expect($decoded)->toHaveKey('other');
    expect($decoded)->toHaveKey('nested.key'); // From fixture
    expect($decoded)->toHaveKey('servers.new-server');
    expect($decoded['servers']['new-server']['command'])->toBe('npm');
});

test('adds to existing mcpServers in plain JSON', function (): void {
    $writtenPath = '';
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-with-servers.json'),
        capturedPath: $writtenPath,
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(200);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    expect($result)->toBeTrue();

    $decoded = json_decode((string) $writtenContent, true);

    expect($decoded)->toHaveKey('mcpServers.existing-server'); // Original preserved
    expect($decoded)->toHaveKey('mcpServers.boost'); // New server added
    expect($decoded['mcpServers']['boost']['command'])->toBe('php');
});

test('preserves complex JSON5 features that VS Code supports', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp.json5'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(1000);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->configKey('servers') // mcp.json5 uses "servers", not "mcpServers"
        ->addServer('test', 'cmd')
        ->save();

    expect($result)->toBeTrue();
    expect($writtenContent)->toContain('"test"'); // New server added
    expect($writtenContent)->toContain('// Here are comments within my JSON'); // Preserve block comments
    expect($writtenContent)->toContain("// I'm trailing"); // Preserve inline comments
    expect($writtenContent)->toContain('// Ooo, pretty cool'); // Preserve comments in arrays
    expect($writtenContent)->toContain('MYSQL_HOST'); // Preserve complex nested structure
});

test('detects plain JSON with comments inside strings as safe', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-comments-in-strings.json'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(200);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->addServer('new-server', 'test-cmd')
        ->save();

    expect($result)->toBeTrue();

    $decoded = json_decode((string) $writtenContent, true);
    expect($decoded)->toHaveKey('exampleCode'); // Original preserved
    expect($decoded)->toHaveKey('mcpServers.new-server'); // New server added
    expect($decoded['exampleCode'])->toContain('// here is the example code'); // Comment in string preserved
});

test('hasUnquotedComments detects comments correctly', function (string $content, bool $expected, string $description): void {
    $writer = new FileWriter('/tmp/test.json');
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('hasUnquotedComments');

    $result = $method->invokeArgs($writer, [$content]);

    expect($result)->toBe($expected, $description);
})->with(commentDetectionCases());

test('trailing comma detection works across newlines', function (string $content, bool $expected, string $description): void {
    $writer = new FileWriter('/tmp/test.json');
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isPlainJson');

    $result = $method->invokeArgs($writer, [$content]);

    expect($result)->toBe($expected, $description);
})->with(trailingCommaCases());

test('generateServerJson creates correct JSON snippet', function (): void {
    $writer = new FileWriter('/tmp/test.json');
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('generateServerJson');

    // Test with simple server
    $result = $method->invokeArgs($writer, ['boost', ['command' => 'php']]);
    expect($result)->toBe('"boost": {
    "command": "php"
}');

    // Test with full server config
    $result = $method->invokeArgs($writer, ['mysql', [
        'command' => 'npx',
        'args' => ['@benborla29/mcp-server-mysql'],
        'env' => ['DB_HOST' => 'localhost'],
    ]]);
    expect($result)->toBe('"mysql": {
    "command": "npx",
    "args": [
        "@benborla29/mcp-server-mysql"
    ],
    "env": {
        "DB_HOST": "localhost"
    }
}');
});

test('fixture mcp-no-configkey.json5 is detected as JSON5 and will use injectNewConfigKey', function (): void {
    $content = fixture('mcp-no-configkey.json5');
    $writer = new FileWriter('/tmp/test.json');
    $reflection = new ReflectionClass($writer);

    // Verify it's detected as JSON5 (not plain JSON)
    $isPlainJsonMethod = $reflection->getMethod('isPlainJson');

    $isPlainJson = $isPlainJsonMethod->invokeArgs($writer, [$content]);
    expect($isPlainJson)->toBeFalse('Should be detected as JSON5 due to comments');

    // Verify it doesn't have mcpServers key (will use injectNewConfigKey path)
    $configKeyPattern = '/["\']mcpServers["\']\\s*:\\s*\\{/';
    $hasConfigKey = preg_match($configKeyPattern, $content);
    expect($hasConfigKey)->toBe(0, 'Should not have mcpServers key, triggering injectNewConfigKey');
});

test('injects new configKey when it does not exist', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-no-configkey.json5'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(200);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    expect($result)->toBeTrue();
    expect($writtenContent)->toContain('"mcpServers"');
    expect($writtenContent)->toContain('"boost"');
    expect($writtenContent)->toContain('"php"');
    expect($writtenContent)->toContain('// No mcpServers key at all'); // Preserve existing comments
});

test('injects into existing configKey preserving JSON5 features', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp.json5'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(1000);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->configKey('servers') // mcp.json5 uses "servers" not "mcpServers"
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    expect($result)->toBeTrue();
    expect($writtenContent)->toContain('"boost"'); // New server added
    expect($writtenContent)->toContain('mysql'); // Existing server preserved
    expect($writtenContent)->toContain('laravel-boost'); // Existing server preserved
    expect($writtenContent)->toContain('// Here are comments within my JSON'); // Comments preserved
    expect($writtenContent)->toContain('// Ooo, pretty cool'); // Inline comments preserved
});

test("injecting twice into existing JSON 5 doesn't cause duplicates", function (): void {
    $capturedContent = '';

    File::clearResolvedInstances();
    File::partialMock();

    File::shouldReceive('ensureDirectoryExists')->once();
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('size')->andReturn(1000);
    File::shouldReceive('get')->andReturn(fixture('mcp.json5'));
    File::shouldReceive('put')
        ->with(
            Mockery::capture($capturedPath),
            Mockery::capture($capturedContent)
        )
        ->andReturn(true);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->configKey('servers') // mcp.json5 uses "servers" not "mcpServers"
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    $boostCounts = substr_count($capturedContent, '"boost"');
    expect($result)->toBeTrue();
    expect($boostCounts)->toBe(1);
    expect($capturedContent)->toContain('"boost"'); // New server added
    expect($capturedContent)->toContain('mysql'); // Existing server preserved
    expect($capturedContent)->toContain('laravel-boost'); // Existing server preserved
    expect($capturedContent)->toContain('// Here are comments within my JSON'); // Comments preserved
    expect($capturedContent)->toContain('// Ooo, pretty cool'); // Inline comments preserved

    $newContent = $capturedContent;

    File::clearResolvedInstances();
    File::partialMock();

    File::shouldReceive('ensureDirectoryExists')->once();
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('size')->andReturn(1000);
    File::shouldReceive('get')->andReturn($newContent);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->configKey('servers')
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    // Second call should return true but not modify the file since boost already exists
    expect($result)->toBeTrue();

    // We should still have only one instance of the boost MCP server
    $boostCounts = substr_count($capturedContent, '"boost"');
    expect($boostCounts)->toBe(1);
});

test('injects into empty configKey object', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-empty-configkey.json5'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(200);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    expect($result)->toBeTrue();
    expect($writtenContent)->toContain('"boost"'); // New server added
    expect($writtenContent)->toContain('// Empty mcpServers object'); // Comments preserved
    expect($writtenContent)->toContain('test_input'); // Existing content preserved
});

test('preserves trailing commas when injecting into existing servers', function (): void {
    $writtenContent = '';

    mockFileOperations(
        fileExists: true,
        content: fixture('mcp-trailing-comma.json5'),
        capturedContent: $writtenContent
    );

    File::shouldReceive('size')->andReturn(200);

    $result = (new FileWriter('/path/to/mcp.json'))
        ->addServer('boost', 'php', ['artisan', 'boost:mcp'])
        ->save();

    expect($result)->toBeTrue()
        ->and($writtenContent)->toContain('"boost"') // New server added
        ->and($writtenContent)->toContain('existing-server') // Existing server preserved
        ->and($writtenContent)->toContain('// Trailing comma here') // Comments preserved
        ->and($writtenContent)->toContain('arg1'); // Existing args preserved
});

test('detectIndentation works correctly with various patterns', function (string $content, int $position, int $expected, string $description): void {
    $writer = new FileWriter('/tmp/test.json');

    $result = $writer->detectIndentation($content, $position);

    expect($result)->toBe($expected, $description);
})->with(indentationDetectionCases());

function mockFileOperations(bool $fileExists = false, string $content = '{}', bool $writeSuccess = true, ?string &$capturedPath = null, ?string &$capturedContent = null): void
{
    // Clear any existing File facade mock
    File::clearResolvedInstances();
    File::partialMock();

    File::shouldReceive('ensureDirectoryExists')->once();
    File::shouldReceive('exists')->andReturn($fileExists);

    if ($fileExists) {
        File::shouldReceive('get')->once()->andReturn($content);
    }

    // Check if either capture parameter is provided
    if (! is_null($capturedPath) || ! is_null($capturedContent)) {
        File::shouldReceive('put')
            ->once()
            ->with(
                Mockery::capture($capturedPath),
                Mockery::capture($capturedContent)
            )
            ->andReturn($writeSuccess);
    } else {
        File::shouldReceive('put')->once()->andReturn($writeSuccess);
    }
}

function newFileServerConfigurations(): array
{
    return [
        'single server without args or env' => [
            'servers',
            [
                'im-new-here' => ['command' => './start-mcp'],
            ],
            '{"servers":{"im-new-here":{"command":"./start-mcp"}}}',
        ],
        'single server with args' => [
            'mcpServers',
            [
                'boost' => [
                    'command' => 'php',
                    'args' => ['artisan', 'boost:mcp'],
                ],
            ],
            '{"mcpServers":{"boost":{"command":"php","args":["artisan","boost:mcp"]}}}',
        ],
        'single server with env' => [
            'servers',
            [
                'mysql' => [
                    'command' => 'npx',
                    'env' => ['DB_HOST' => 'localhost', 'DB_PORT' => '3306'],
                ],
            ],
            '{"servers":{"mysql":{"command":"npx","env":{"DB_HOST":"localhost","DB_PORT":"3306"}}}}',
        ],
        'multiple servers mixed' => [
            'mcpServers',
            [
                'boost' => [
                    'command' => 'php',
                    'args' => ['artisan', 'boost:mcp'],
                ],
                'mysql' => [
                    'command' => 'npx',
                    'args' => ['@benborla29/mcp-server-mysql'],
                    'env' => ['DB_HOST' => 'localhost'],
                ],
            ],
            '{"mcpServers":{"boost":{"command":"php","args":["artisan","boost:mcp"]},"mysql":{"command":"npx","args":["@benborla29/mcp-server-mysql"],"env":{"DB_HOST":"localhost"}}}}',
        ],
        'custom config key' => [
            'customKey',
            [
                'test' => ['command' => 'test-cmd'],
            ],
            '{"customKey":{"test":{"command":"test-cmd"}}}',
        ],
    ];
}

function commentDetectionCases(): array
{
    return [
        'plain JSON no comments' => [
            '{"servers": {"test": {"command": "npm"}}}',
            false,
            'Plain JSON should return false',
        ],
        'JSON with comments in strings' => [
            '{"exampleCode": "// here is the example code\n<?php", "url": "https://example.com/path"}',
            false,
            'Comments inside strings should not be detected as real comments',
        ],
        'JSON5 with real line comments' => [
            '{"servers": {"test": "value"} // this is a real comment}',
            true,
            'Real JSON5 line comments should be detected',
        ],
        'JSON5 with comment at start of line' => [
            '{\n  // This is a comment\n  "servers": {}\n}',
            true,
            'Line comments at start should be detected',
        ],
        'complex string with escaped quotes' => [
            '{"code": "console.log(\\"// not a comment\\");", "other": "value"}',
            false,
            'Comments in strings with escaped quotes should not be detected',
        ],
        'multiple comments in strings' => [
            '{"example1": "// comment 1", "example2": "some // comment 2 here"}',
            false,
            'Multiple comments in different strings should not be detected',
        ],
        'mixed real and string comments' => [
            '{"example": "// fake comment"} // real comment',
            true,
            'Should detect real comment even when fake ones exist in strings',
        ],
        'empty string' => [
            '',
            false,
            'Empty string should return false',
        ],
        'single slash not comment' => [
            '{"path": "/usr/bin/test"}',
            false,
            'Single slash should not be detected as comment',
        ],
    ];
}

function trailingCommaCases(): array
{
    return [
        'valid JSON no trailing comma' => [
            '{"servers": {"test": "value"}}',
            true,
            'Valid JSON should return true (is plain JSON)',
        ],
        'trailing comma in object same line' => [
            '{"servers": {"test": "value",}}',
            false,
            'Trailing comma in object should return false (is JSON5)',
        ],
        'trailing comma in array same line' => [
            '{"items": ["a", "b", "c",]}',
            false,
            'Trailing comma in array should return false (is JSON5)',
        ],
        'trailing comma across newlines in object' => [
            "{\n  \"servers\": {\n    \"test\": \"value\",\n  }\n}",
            false,
            'Trailing comma across newlines in object should be detected',
        ],
        'trailing comma across newlines in array' => [
            "{\n  \"items\": [\n    \"a\",\n    \"b\",\n  ]\n}",
            false,
            'Trailing comma across newlines in array should be detected',
        ],
        'trailing comma with tabs and spaces' => [
            "{\n  \"test\": \"value\",\t \n}",
            false,
            'Trailing comma with mixed whitespace should be detected',
        ],
        'comma in string not trailing' => [
            '{"example": "value,", "other": "test"}',
            true,
            'Comma inside string should not be detected as trailing',
        ],
    ];
}

function indentationDetectionCases(): array
{
    return [
        'mcp.json5 servers indentation' => [
            "{\n    // Here are comments within my JSON\n    \"servers\": {\n        \"mysql\": {\n            \"command\": \"npx\"\n        },\n        \"laravel-boost\": {\n            \"command\": \"php\"\n        }\n    },\n    \"inputs\": []\n}",
            200, // Position near end of servers block
            8,
            'Should detect 8 spaces for server definitions in mcp.json5',
        ],
        'nested object with 4-space base indent' => [
            "{\n    \"config\": {\n        \"server1\": {\n            \"command\": \"test\"\n        }\n    }\n}",
            80,
            8,
            'Should detect 8 spaces for nested server definitions',
        ],
        'no previous server definitions' => [
            "{\n    \"inputs\": []\n}",
            20,
            8,
            'Should fallback to 8 spaces when no server definitions found',
        ],
        'deeper nesting with 2-space indent' => [
            "{\n  \"config\": {\n    \"servers\": {\n      \"mysql\": {\n        \"command\": \"test\"\n      }\n    }\n  }\n}",
            80,
            6,
            'Should detect correct indentation in deeply nested structures',
        ],
        'single server definition at root level' => [
            "{\n\"mysql\": {\n  \"command\": \"npx\"\n}\n}",
            30,
            0,
            'Should detect no indentation for root-level server definitions',
        ],
        'multiple server definitions with consistent indentation' => [
            "{\n    \"servers\": {\n        \"mysql\": {\n            \"command\": \"npx\"\n        },\n        \"postgres\": {\n            \"command\": \"pg\"\n        }\n    }\n}",
            150,
            8,
            'Should consistently detect indentation across multiple servers',
        ],
        'server definition with comments' => [
            "{\n    // Comment here\n    \"servers\": {\n        \"mysql\": { // inline comment\n            \"command\": \"npx\"\n        }\n    }\n}",
            120,
            8,
            'Should detect indentation correctly when comments are present',
        ],
        'empty content' => [
            '',
            0,
            8,
            'Should fallback to 8 spaces for empty content',
        ],
    ];
}
