<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for AI coding assistants that receive guidelines.
 */
interface SupportsGuidelines
{
    /**
     * Get the file path where AI guidelines should be written.
     */
    public function guidelinesPath(): string;

    /**
     * Determine if the guideline file requires frontmatter.
     */
    public function frontmatter(): bool;

    /**
     * Transform the generated guidelines' Markdown.
     */
    public function transformGuidelines(string $markdown): string;
}
