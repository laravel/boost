<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for AI coding assistants that receive guidelines.
 */
interface SupportGuidelines
{
    public function name(): string;

    public function displayName(): string;

    /**
     * Get the file path where AI guidelines should be written.
     *
     * @return string The relative or absolute path to the guideline file
     */
    public function guidelinesPath(): string;

    /**
     * Determine if the guideline file requires frontmatter.
     */
    public function frontmatter(): bool;
}
