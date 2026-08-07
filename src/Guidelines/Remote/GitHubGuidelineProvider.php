<?php

declare(strict_types=1);

namespace Laravel\Boost\Guidelines\Remote;

use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\InteractsWithGitHub;
use Laravel\Boost\Support\GitHubRepository;

class GitHubGuidelineProvider
{
    use InteractsWithGitHub;

    protected string $defaultGuidelinePath = '.ai/guidelines';

    public function __construct(protected GitHubRepository $repository)
    {
        //
    }

    /**
     * @return Collection<string, RemoteGuideline>
     */
    public function discoverGuidelines(): Collection
    {
        $tree = $this->fetchRepositoryTree();

        if ($tree === null) {
            return collect();
        }

        $rootPath = $this->guidelineRootPath();
        $prefix = $rootPath.'/';

        return collect($tree['tree'])
            ->filter(fn (array $item): bool => ($item['type'] ?? null) === 'blob' && is_string($item['path'] ?? null))
            ->map(function (array $item) use ($prefix): ?RemoteGuideline {
                $path = (string) $item['path'];

                if (! str_starts_with($path, $prefix) || ! str_ends_with($path, '.md')) {
                    return null;
                }

                $relativePath = substr($path, strlen($prefix));
                $name = substr($relativePath, 0, -3);

                return new RemoteGuideline(
                    name: $name,
                    repo: $this->repository->fullName(),
                    path: $path,
                    relativePath: $relativePath,
                );
            })
            ->filter()
            ->keyBy(fn (RemoteGuideline $guideline): string => $guideline->name);
    }

    public function downloadGuideline(RemoteGuideline $guideline, string $targetPath): bool
    {
        $tree = $this->fetchRepositoryTree();

        if ($tree === null) {
            return false;
        }

        $file = collect($tree['tree'])->first(
            fn (array $item): bool => ($item['type'] ?? null) === 'blob' && ($item['path'] ?? null) === $guideline->path
        );

        if (! is_array($file)) {
            return false;
        }

        return $this->downloadFiles([$file], $targetPath, $this->guidelineRootPath());
    }

    protected function guidelineRootPath(): string
    {
        return $this->repository->path !== '' ? $this->repository->path : $this->defaultGuidelinePath;
    }
}
