<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;
use Symfony\Component\Process\Process;

test('builds correct command with code', function (): void {
    $tool = new class extends Tinker
    {
        public function exposeBuildCommand(string $code): array
        {
            return $this->buildCommand($code);
        }
    };

    $command = $tool->exposeBuildCommand('echo "hello"');

    expect($command)->toBe([
        PHP_BINARY,
        base_path('artisan'),
        'tinker',
        '--execute=echo "hello"',
    ]);
});

test('sanitizes code by stripping php tags and trimming', function (): void {
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

test('clamps timeout between 1 and 600 seconds', function (): void {
    $tool = new class extends Tinker
    {
        public function exposeClampTimeout(mixed $timeout): int
        {
            return $this->clampTimeout($timeout);
        }
    };

    expect($tool->exposeClampTimeout(null))->toBe(180)
        ->and($tool->exposeClampTimeout(-10))->toBe(1)
        ->and($tool->exposeClampTimeout(0))->toBe(1)
        ->and($tool->exposeClampTimeout(9999))->toBe(600)
        ->and($tool->exposeClampTimeout(60))->toBe(60);
});

test('returns error for empty code', function (): void {
    $tool = new Tinker;

    foreach ([null, '', '   ', '<?php ?>'] as $code) {
        $response = $tool->handle(new Request(['code' => $code]));

        expect($response)->isToolResult()->toolHasError();
    }
});

test('tinker executes code in laravel context', function (): void {
    $process = new Process([
        PHP_BINARY,
        'vendor/bin/testbench',
        'tinker',
        '--execute=echo config("app.name");',
    ]);
    $process->run();

    expect($process->isSuccessful())->toBeTrue()
        ->and($process->getOutput())->toContain('Laravel');
});

test('handles syntax errors gracefully', function (): void {
    $tool = new Tinker;

    $response = $tool->handle(new Request([
        'code' => 'echo "missing semicolon',
    ]));

    $result = json_decode((string) $response->content(), true);

    expect($result['output'])->toContain('InvalidArgumentException')
        ->and($result['output'])->toContain('Unexpected end of input');
});
