<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Contracts\SupportsCommands;
use RuntimeException;

class CommandWriter
{
    use RendersBladeGuidelines;

    public const SUCCESS = 0;

    public const UPDATED = 1;

    public const FAILED = 2;

    public function __construct(protected SupportsCommands $agent)
    {
        //
    }

    public function write(Command $command): int
    {
        if (! $this->isValidCommandName($command->name)) {
            throw new RuntimeException("Invalid command name: {$command->name}");
        }

        if (! is_file($command->path)) {
            return self::FAILED;
        }

        $targetPath = $this->targetPath($command->name);
        $existed = is_file($targetPath);

        if (! $this->ensureDirectoryExists(dirname($targetPath))) {
            return self::FAILED;
        }

        $content = $command->isBlade
            ? $this->renderBladeFile($command->path)
            : (string) file_get_contents($command->path);

        $content = MarkdownFormatter::format(trim($content));

        $written = file_put_contents($targetPath, $this->ensureTrailingNewline($content));

        if ($written === false) {
            return self::FAILED;
        }

        return $existed ? self::UPDATED : self::SUCCESS;
    }

    /**
     * @param  Collection<string, Command>  $commands
     * @return array<string, int>
     */
    public function writeAll(Collection $commands): array
    {
        return $commands
            ->mapWithKeys(fn (Command $command): array => [$command->name => $this->write($command)])
            ->all();
    }

    /**
     * @param  Collection<string, Command>  $commands
     * @param  array<int, string>  $previouslyTrackedCommands
     * @return array<string, int>
     */
    public function sync(Collection $commands, array $previouslyTrackedCommands = []): array
    {
        $written = $this->writeAll($commands);

        $newNames = $commands->keys()->all();
        $staleNames = array_values(array_diff($previouslyTrackedCommands, $newNames));

        $this->removeStale($staleNames);

        return $written;
    }

    public function remove(string $commandName): bool
    {
        if (! $this->isValidCommandName($commandName)) {
            return false;
        }

        $targetPath = $this->targetPath($commandName);

        if (! file_exists($targetPath)) {
            return true;
        }

        return @unlink($targetPath);
    }

    /**
     * @param  array<int, string>  $commandNames
     * @return array<string, bool>
     */
    public function removeStale(array $commandNames): array
    {
        $results = [];

        foreach ($commandNames as $name) {
            $results[$name] = $this->remove($name);
        }

        return $results;
    }

    protected function targetPath(string $commandName): string
    {
        $filename = $this->agent->commandFilename($commandName.'.md');

        return base_path($this->agent->commandsPath().DIRECTORY_SEPARATOR.$filename);
    }

    protected function ensureTrailingNewline(string $content): string
    {
        return str_ends_with($content, "\n") ? $content : $content."\n";
    }

    protected function ensureDirectoryExists(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0755, true);
    }

    protected function isValidCommandName(string $name): bool
    {
        $hasPathTraversal = str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\');

        return ! $hasPathTraversal && trim($name) !== '';
    }
}
