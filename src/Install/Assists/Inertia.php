<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Assists;

use Laravel\Boost\Support\PackageRegistry;
use Laravel\Roster\ProjectManager;

class Inertia
{
    public function __construct(private ProjectManager $project)
    {
        //
    }

    public function gte(string $version): bool
    {
        if ($this->project->php()->uses(PackageRegistry::INERTIA_LARAVEL, ">={$version}")) {
            return true;
        }

        if ($this->project->js()->uses(PackageRegistry::INERTIA_REACT, ">={$version}")) {
            return true;
        }

        if ($this->project->js()->uses(PackageRegistry::INERTIA_SVELTE, ">={$version}")) {
            return true;
        }

        return $this->project->js()->uses(PackageRegistry::INERTIA_VUE, ">={$version}");
    }

    public function hasFormComponent(): bool
    {
        return $this->gte('2.1.0');
    }

    public function hasFormComponentResets(): bool
    {
        return $this->gte('2.1.2');
    }

    public function pagesDirectory(): string
    {
        $jsPath = base_path('resources/js');

        if (is_dir($jsPath)) {
            $entries = @scandir($jsPath);

            if ($entries !== false && in_array('pages', $entries, true)) {
                return 'resources/js/pages';
            }
        }

        return 'resources/js/Pages';
    }
}
