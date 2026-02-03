<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;
use Laravel\Tinker\TinkerServiceProvider;

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
    $this->app->register(TinkerServiceProvider::class);

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
    $this->app->register(TinkerServiceProvider::class);

    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo 1 + 1;',
        'timeout' => 30,
    ]));

    $result = json_decode((string) $response->content(), true);

    expect($result)->toHaveKey('output')
        ->and($result['output'])->toContain('2');
});
