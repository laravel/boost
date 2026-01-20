<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for agents that support Agent Skills
 */
interface SupportsSkills
{
    /**
     * Get the file path where agent skills should be written.
     */
    public function skillsPath(): string;
}
