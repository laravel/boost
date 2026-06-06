<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsCommands;
use Laravel\Boost\Install\Command;
use Laravel\Boost\Install\CommandWriter;

function cleanupCommandsDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
    }

    @rmdir($path);
}

it('writes a markdown command to the agent commands directory', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('refactor.md')->andReturn('refactor.md');

    $command = new Command(
        name: 'refactor',
        path: fixture('commands/refactor.md'),
        isBlade: false,
    );

    $writer = new CommandWriter($agent);
    $result = $writer->write($command);

    expect($result)->toBe(CommandWriter::SUCCESS)
        ->and($absoluteTarget.'/refactor.md')->toBeFile()
        ->and(file_get_contents($absoluteTarget.'/refactor.md'))
        ->toContain('Refactor the selected code')
        ->toEndWith("\n");

    cleanupCommandsDirectory($absoluteTarget);
});

it('returns UPDATED when the command file already exists', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    mkdir($absoluteTarget, 0755, true);
    file_put_contents($absoluteTarget.'/refactor.md', 'old content');

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('refactor.md')->andReturn('refactor.md');

    $command = new Command(
        name: 'refactor',
        path: fixture('commands/refactor.md'),
        isBlade: false,
    );

    $result = (new CommandWriter($agent))->write($command);

    expect($result)->toBe(CommandWriter::UPDATED)
        ->and(file_get_contents($absoluteTarget.'/refactor.md'))->toContain('Refactor the selected code');

    cleanupCommandsDirectory($absoluteTarget);
});

it('returns FAILED when the source file does not exist', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('missing.md')->andReturn('missing.md');

    $command = new Command(
        name: 'missing',
        path: '/nonexistent/'.uniqid().'.md',
        isBlade: false,
    );

    $result = (new CommandWriter($agent))->write($command);

    expect($result)->toBe(CommandWriter::FAILED);
});

it('renders blade commands to markdown', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('review.md')->andReturn('review.md');

    $command = new Command(
        name: 'review',
        path: fixture('commands/review.blade.php'),
        isBlade: true,
    );

    $result = (new CommandWriter($agent))->write($command);

    expect($result)->toBe(CommandWriter::SUCCESS)
        ->and($absoluteTarget.'/review.md')->toBeFile();

    $content = file_get_contents($absoluteTarget.'/review.md');

    expect($content)
        ->toContain('The answer is 2')
        ->not->toContain('{{ 1 + 1 }}');

    cleanupCommandsDirectory($absoluteTarget);
});

it('applies the Copilot prompt.md suffix via commandFilename', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('refactor.md')->andReturn('refactor.prompt.md');

    $command = new Command(
        name: 'refactor',
        path: fixture('commands/refactor.md'),
        isBlade: false,
    );

    $result = (new CommandWriter($agent))->write($command);

    expect($result)->toBe(CommandWriter::SUCCESS)
        ->and($absoluteTarget.'/refactor.prompt.md')->toBeFile()
        ->and($absoluteTarget.'/refactor.md')->not->toBeFile();

    cleanupCommandsDirectory($absoluteTarget);
});

it('throws on path-traversal command names', function (string $maliciousName): void {
    $agent = Mockery::mock(SupportsCommands::class);

    $command = new Command(
        name: $maliciousName,
        path: fixture('commands/refactor.md'),
        isBlade: false,
    );

    expect(fn () => (new CommandWriter($agent))->write($command))
        ->toThrow(RuntimeException::class, 'Invalid command name');
})->with([
    '../../../etc/passwd',
    'foo/bar',
    'foo\\bar',
    '..',
]);

it('removes a command file', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    mkdir($absoluteTarget, 0755, true);
    file_put_contents($absoluteTarget.'/refactor.md', 'x');

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('refactor.md')->andReturn('refactor.md');

    $writer = new CommandWriter($agent);

    expect($writer->remove('refactor'))->toBeTrue()
        ->and($absoluteTarget.'/refactor.md')->not->toBeFile();

    cleanupCommandsDirectory($absoluteTarget);
});

it('returns true when removing a non-existent command', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->with('missing.md')->andReturn('missing.md');

    expect((new CommandWriter($agent))->remove('missing'))->toBeTrue();
});

it('returns false when removing a command with an invalid name', function (): void {
    $agent = Mockery::mock(SupportsCommands::class);

    $writer = new CommandWriter($agent);

    expect($writer->remove('../malicious'))->toBeFalse()
        ->and($writer->remove('foo/bar'))->toBeFalse();
});

it('syncs by writing new and removing stale, leaving untracked files alone', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    mkdir($absoluteTarget, 0755, true);
    file_put_contents($absoluteTarget.'/stale.md', 'stale');
    file_put_contents($absoluteTarget.'/untracked.md', 'user-written');

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->andReturnUsing(fn (string $base): string => $base);

    $commands = collect([
        'refactor' => new Command('refactor', fixture('commands/refactor.md'), false),
    ]);

    $result = (new CommandWriter($agent))->sync($commands, ['stale']);

    expect($result['refactor'])->toBe(CommandWriter::SUCCESS)
        ->and($absoluteTarget.'/refactor.md')->toBeFile()
        ->and($absoluteTarget.'/stale.md')->not->toBeFile()
        ->and($absoluteTarget.'/untracked.md')->toBeFile();

    cleanupCommandsDirectory($absoluteTarget);
});

it('sync uses commandFilename when removing stale entries', function (): void {
    $relativeTarget = '.boost-test-commands-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    mkdir($absoluteTarget, 0755, true);
    file_put_contents($absoluteTarget.'/stale.prompt.md', 'stale');

    $agent = Mockery::mock(SupportsCommands::class);
    $agent->shouldReceive('commandsPath')->andReturn($relativeTarget);
    $agent->shouldReceive('commandFilename')->andReturnUsing(fn (string $base): string => str_replace('.md', '.prompt.md', $base));

    $result = (new CommandWriter($agent))->sync(collect(), ['stale']);

    expect($result)->toBeEmpty()
        ->and($absoluteTarget.'/stale.prompt.md')->not->toBeFile();

    cleanupCommandsDirectory($absoluteTarget);
});
