<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

interface SupportSkills
{
    public function name(): string;

    public function displayName(): string;

    /**
     * Get the file path where Agent Skills should be written.
     *
     * @return string The relative or absolute path to the guideline file
     */
    public function skillsPath(): string;
}
