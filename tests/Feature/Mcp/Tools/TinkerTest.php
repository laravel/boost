<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Tinker\TinkerServiceProvider;

beforeEach(function (): void {
    $this->app->register(TinkerServiceProvider::class);
});

it('sanitizes code by stripping php tags and trimming', function (): void {
    $tool = new class extends Tinker
    {
        public function exposeSanitizeCode(string $code): string
        {
            return $this->sanitizeCode($code);
        }
    };

    expect($tool->exposeSanitizeCode('<?php echo 1; ?>'))->toBe('echo 1;')
        ->and($tool->exposeSanitizeCode('  echo 1  '))->toBe('echo 1')
        ->and($tool->exposeSanitizeCode('<?php ?>'))->toBe('')
        ->and($tool->exposeSanitizeCode('   '))->toBe('');
});

it('returns error for empty code', function (): void {
    $tool = new Tinker;

    foreach ([null, '', '   ', '<?php ?>'] as $code) {
        $response = $tool->handle(new Request(['code' => $code]));

        expect($response)->isToolResult()->toolHasError();
    }
});

it('handles syntax errors gracefully', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo "missing semicolon',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    expect($result)->toHaveKey('error')
        ->and($result['error'])->toBeString();
});

it('executes valid code successfully', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo 1 + 1;',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest executes valid code');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('2');
});

it('preserves return values from executed code', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'return 1 + 1;',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest preserves return values');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('=>')
        ->and($result['output'])->toContain('2');
});

it('captures both echo output and return values', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo "debug"; return 42;',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest captures echo and return');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('debug')
        ->and($result['output'])->toContain('=>')
        ->and($result['output'])->toContain('42');
});

it('returns null values silently without extra output', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo "test";',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest returns null values');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toBe('test')
        ->and($result['output'])->not->toContain('=>');
});

it('returns runtime errors in output for AI interpretation', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'nonExistentFunction();',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest returns runtime errors');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('nonExistentFunction');
});

it('returns division by zero errors in output', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'return 1 / 0;',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    dumpResponseIfMissingOutput($response, $result, 'TinkerTest returns division by zero');

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('Division by zero');
});

function dumpResponseIfMissingOutput(Response $response, mixed $result, string $label): void
{
    if (! is_array($result) || ! array_key_exists('output', $result)) {
        fwrite(STDERR, PHP_EOL.$label.' missing output. Raw response: '.(string) $response->content().PHP_EOL);
    }
}
