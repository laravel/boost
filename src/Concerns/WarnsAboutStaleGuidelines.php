<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Install\Agents\Agent;

trait WarnsAboutStaleGuidelines
{
    /**
     * @param  Collection<array-key, Agent>  $agents
     */
    protected function warnAboutStaleGuidelines(Collection $agents): void
    {
        $updated = $agents
            ->map(fn (Agent $agent): string|false => $agent instanceof SupportsGuidelines ? realpath($agent->guidelinesPath()) : false)
            ->filter()
            ->unique();

        // Every path Boost writes guidelines to by default, now or in earlier versions.
        $stale = collect(['AGENTS.md', 'CLAUDE.md', 'GEMINI.md', '.cursor/rules/laravel-boost.mdc', '.github/copilot-instructions.md', '.junie/guidelines.md'])
            ->map(fn (string $path): string|false => realpath(base_path($path)))
            ->filter()
            ->diff($updated)
            ->filter(fn (string $path): bool => $this->countGuidelineBlocks($path) > 0)
            ->merge($updated->filter(fn (string $path): bool => $this->countGuidelineBlocks($path) > 1));

        if ($stale->isEmpty()) {
            return;
        }

        $this->warn('These files have a Boost guidelines block that was not updated, so agents reading them may follow outdated guidelines:');

        foreach ($stale as $path) {
            $this->line('  - '.str_replace(realpath(base_path()).DIRECTORY_SEPARATOR, '', $path));
        }
    }

    private function countGuidelineBlocks(string $path): int
    {
        return substr_count((string) file_get_contents($path), '<laravel-boost-guidelines>');
    }
}
