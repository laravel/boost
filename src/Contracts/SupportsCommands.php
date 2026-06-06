<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for agents that support project-level slash commands.
 */
interface SupportsCommands
{
    /**
     * Get the directory where command files should be written.
     */
    public function commandsPath(): string;

    /**
     * Transform a base command filename (e.g. "refactor.md") into the
     * on-disk filename the agent expects (e.g. "refactor.prompt.md").
     */
    public function commandFilename(string $baseName): string;
}
