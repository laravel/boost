<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\InteractsWithGitHub;
use Laravel\Boost\Support\GitHubRepository;

class GitHubSkillProvider
{
    use InteractsWithGitHub;

    public function __construct(protected GitHubRepository $repository)
    {
        //
    }

    /**
     * @return Collection<string, RemoteSkill>
     */
    public function discoverSkills(): Collection
    {
        $tree = $this->fetchRepositoryTree();

        if ($tree === null) {
            return collect();
        }

        $basePath = $this->repository->path;

        $skillMarkers = collect($tree['tree'])
            ->filter(fn (array $item): bool => $item['type'] === 'blob' && basename((string) $item['path']) === 'SKILL.md');

        if ($basePath !== '') {
            $prefix = $basePath.'/';

            $skillMarkers = $skillMarkers->filter(function (array $item) use ($prefix): bool {
                $skillDir = dirname((string) $item['path']);

                return str_starts_with($skillDir, $prefix) && ! str_contains(substr($skillDir, strlen($prefix)), '/');
            });
        }

        return $skillMarkers
            ->map(fn (array $item): RemoteSkill => new RemoteSkill(
                name: basename(dirname((string) $item['path'])),
                repo: $this->repository->fullName(),
                path: dirname((string) $item['path']),
            ))
            ->keyBy(fn (RemoteSkill $skill): string => $skill->name);
    }

    public function downloadSkill(RemoteSkill $skill, string $targetPath): bool
    {
        $tree = $this->fetchRepositoryTree();

        if ($tree === null) {
            return false;
        }

        $skillFiles = $this->extractSkillFilesFromTree($tree['tree'], $skill->path);

        if ($skillFiles->isEmpty()) {
            return false;
        }

        $files = $skillFiles
            ->filter(fn (array $item): bool => $item['type'] === 'blob')
            ->reject(fn (array $item): bool => preg_match('/\.(php\d?|phar|phtml)$/i', (string) $item['path']) === 1);

        if (! $files->contains(fn (array $item): bool => basename((string) $item['path']) === 'SKILL.md')) {
            return false;
        }

        if (! $this->ensureDirectoryExists($targetPath)) {
            return false;
        }

        return $this->downloadFiles($files->toArray(), $targetPath, $skill->path);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return Collection<int, array<string, mixed>>
     */
    protected function extractSkillFilesFromTree(array $tree, string $skillPath): Collection
    {
        $prefix = $skillPath.'/';

        return collect($tree)
            ->filter(fn (array $item): bool => str_starts_with((string) $item['path'], $prefix))
            ->values();
    }
}
