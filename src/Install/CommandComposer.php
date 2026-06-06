<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;

class CommandComposer
{
    /** @var Collection<string, Command>|null */
    protected ?Collection $commands = null;

    /**
     * Discover commands from the user's `.ai/commands/` directory.
     *
     * @return Collection<string, Command>
     */
    public function commands(): Collection
    {
        if ($this->commands instanceof Collection) {
            return $this->commands;
        }

        $path = base_path('.ai/commands');

        if (! is_dir($path)) {
            return $this->commands = collect();
        }

        return $this->commands = collect(scandir($path) ?: [])
            ->filter(fn (string $entry): bool => $this->isCandidate($path, $entry))
            ->map(fn (string $entry): Command => $this->makeCommand($path.DIRECTORY_SEPARATOR.$entry, $entry))
            ->keyBy(fn (Command $command): string => $command->name);
    }

    protected function isCandidate(string $path, string $entry): bool
    {
        if ($entry === '.' || $entry === '..') {
            return false;
        }

        if (str_starts_with($entry, '_') || str_starts_with($entry, '.')) {
            return false;
        }

        if (! is_file($path.DIRECTORY_SEPARATOR.$entry)) {
            return false;
        }

        return str_ends_with($entry, '.md') || str_ends_with($entry, '.blade.php');
    }

    protected function makeCommand(string $absolutePath, string $entry): Command
    {
        $isBlade = str_ends_with($entry, '.blade.php');
        $name = $isBlade
            ? substr($entry, 0, -strlen('.blade.php'))
            : substr($entry, 0, -strlen('.md'));

        return new Command(
            name: $name,
            path: $absolutePath,
            isBlade: $isBlade,
        );
    }
}
