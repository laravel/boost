<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Roster\Package;
use Laravel\Roster\ProjectManager;

class Playwright
{
    /** @var array<int, string> */
    public const PACKAGES = [
        '@playwright/test',
        '@playwright/cli',
        'playwright',
    ];

    public const CORE_PACKAGE = 'playwright-core';

    public const COMPONENT_TESTING_SKILL = 'playwright-component-testing';

    public const COMPONENT_TESTING_PACKAGE_PREFIX = '@playwright/experimental-ct-';

    public function __construct(protected ProjectManager $project)
    {
        //
    }

    public function isInstalled(): bool
    {
        return $this->project->js()->usesDirect(self::PACKAGES) && $this->skillsPath() !== null;
    }

    /**
     * Playwright ships its agent skills inside `playwright-core`, which projects install indirectly.
     */
    public function skillsPath(): ?string
    {
        $path = $this->project->js()->package(self::CORE_PACKAGE)?->path();

        if ($path === null) {
            return null;
        }

        $skillsPath = implode(DIRECTORY_SEPARATOR, [$path, 'lib', 'tools', 'skills']);

        return is_dir($skillsPath) ? $skillsPath : null;
    }

    public function usesComponentTesting(): bool
    {
        return $this->project->js()->packages()->contains(
            fn (Package $package): bool => $package->isDirect() && str_starts_with($package->name(), self::COMPONENT_TESTING_PACKAGE_PREFIX)
        );
    }
}
