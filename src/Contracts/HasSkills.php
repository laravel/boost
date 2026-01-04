<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for code environments that support Agent Skills.
 *
 * Agent Skills are modular instruction packages that load on-demand,
 * reducing context usage compared to single large guideline files.
 *
 * @see https://agentskills.io/home
 */
interface HasSkills extends Agent
{
    /**
     * Get the directory where Skills should be written.
     *
     * @return string The relative or absolute path to the directory
     */
    public function skillsPath(): string;
}
