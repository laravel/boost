<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Command;
use Laravel\Boost\Install\CommandComposer;

beforeEach(function (): void {
    $this->commandsDir = base_path('.ai/commands');

    if (is_dir($this->commandsDir)) {
        purgeCommandFixtures($this->commandsDir);
    } else {
        @mkdir($this->commandsDir, 0755, true);
    }
});

afterEach(function (): void {
    purgeCommandFixtures($this->commandsDir);

    if (is_dir($this->commandsDir)) {
        @rmdir($this->commandsDir);
    }
});

function purgeCommandFixtures(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $dir.DIRECTORY_SEPARATOR.$entry;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function writeCommandFixture(string $relative, string $content): string
{
    $path = base_path('.ai/commands').DIRECTORY_SEPARATOR.$relative;
    file_put_contents($path, $content);

    return $path;
}

it('returns an empty collection when .ai/commands does not exist', function (): void {
    // Remove the directory created by beforeEach to simulate absence.
    if (is_dir($this->commandsDir)) {
        @rmdir($this->commandsDir);
    }

    $commands = (new CommandComposer)->commands();

    expect($commands)->toBeInstanceOf(Collection::class)
        ->and($commands)->toBeEmpty();
});

it('discovers markdown commands', function (): void {
    writeCommandFixture('refactor.md', "# Refactor\n\nRefactor it.\n");

    $commands = (new CommandComposer)->commands();

    expect($commands->has('refactor'))->toBeTrue();

    /** @var Command $command */
    $command = $commands->get('refactor');

    expect($command->name)->toBe('refactor')
        ->and($command->isBlade)->toBeFalse()
        ->and($command->path)->toBe($this->commandsDir.DIRECTORY_SEPARATOR.'refactor.md');
});

it('discovers blade commands and strips the .blade.php suffix from the name', function (): void {
    writeCommandFixture('review.blade.php', "# Review\n\n{{ 1 + 1 }}\n");

    $commands = (new CommandComposer)->commands();

    expect($commands->has('review'))->toBeTrue();

    /** @var Command $command */
    $command = $commands->get('review');

    expect($command->isBlade)->toBeTrue()
        ->and($command->path)->toBe($this->commandsDir.DIRECTORY_SEPARATOR.'review.blade.php');
});

it('skips files starting with an underscore or dot', function (): void {
    writeCommandFixture('_partial.md', 'partial');
    writeCommandFixture('.hidden.md', 'hidden');
    writeCommandFixture('keeper.md', 'keeper');

    $commands = (new CommandComposer)->commands();

    expect($commands->keys()->all())->toBe(['keeper']);
});

it('skips files that are not markdown or blade', function (): void {
    writeCommandFixture('notes.txt', 'plain text');
    writeCommandFixture('refactor.md', 'kept');

    $commands = (new CommandComposer)->commands();

    expect($commands->keys()->all())->toBe(['refactor']);
});

it('caches discovered commands across calls', function (): void {
    writeCommandFixture('first.md', 'one');

    $composer = new CommandComposer;
    $initial = $composer->commands();

    writeCommandFixture('second.md', 'two');

    expect($composer->commands())->toBe($initial)
        ->and($composer->commands()->keys()->all())->toBe(['first']);
});
