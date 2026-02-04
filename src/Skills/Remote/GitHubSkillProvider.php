<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubSkillProvider
{
    protected string $defaultBranch = 'main';

    protected string $resolvedPath = '';

    /** @var array<string, mixed>|null */
    protected ?array $cachedTree = null;

    /** @var array<int, string> */
    protected array $commonSkillPaths = [
        'skills',
        '.ai/skills',
        '.cursor/skills',
        '.claude/skills',
    ];

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

        $this->resolvedPath = $this->resolveSkillsPath();

        $directories = $this->findSkillDirectoriesInTree($tree['tree'], $this->resolvedPath);

        if ($directories->isEmpty()) {
            return collect();
        }

        $validSkills = $this->validateSkillDirectories($directories, $tree['tree']);

        return $validSkills->map(fn (array $item): RemoteSkill => new RemoteSkill(
            name: $item['name'],
            repo: $this->repository->fullName(),
            path: $item['path'],
        ))->keyBy(fn (RemoteSkill $skill): string => $skill->name);
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

        if (! $this->ensureDirectoryExists($targetPath)) {
            return false;
        }

        $files = $skillFiles->filter(fn (array $item): bool => $item['type'] === 'blob');
        $directories = $skillFiles->filter(fn (array $item): bool => $item['type'] === 'tree');

        foreach ($directories as $dir) {
            $relativePath = $this->getRelativePath($dir['path'], $skill->path);
            $localPath = $targetPath.'/'.$relativePath;

            if (! $this->ensureDirectoryExists($localPath)) {
                return false;
            }
        }

        return $this->downloadFiles($files->toArray(), $targetPath, $skill->path);
    }

    /**
     * @return array{tree: array<int, array<string, mixed>>, sha: string, url: string, truncated: bool}|null
     *
     * @throws RuntimeException
     */
    protected function fetchRepositoryTree(): ?array
    {
        if ($this->cachedTree !== null) {
            return $this->cachedTree;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
            $this->repository->owner,
            $this->repository->repo,
            $this->defaultBranch
        );

        $response = $this->client()->get($url);

        if ($response->status() === 403) {
            $rateLimitRemaining = $response->header('X-RateLimit-Remaining');
            $rateLimitReset = $response->header('X-RateLimit-Reset');

            if ($rateLimitRemaining === '0') {
                $resetTime = $rateLimitReset
                    ? date('Y-m-d H:i:s', (int) $rateLimitReset)
                    : 'unknown';

                throw new RuntimeException(
                    "GitHub API rate limit exceeded. Rate limit will reset at {$resetTime}. ".
                    'Configure a GitHub token via boost.github.token or services.github.token for higher limits (5000 req/hr vs 60 req/hr).'
                );
            }
        }

        if ($response->failed()) {
            $errorMessage = $response->json('message') ?? 'Unknown error';

            throw new RuntimeException(
                "Failed to fetch repository tree from GitHub: {$errorMessage} (HTTP {$response->status()})"
            );
        }

        $tree = $response->json();

        if (! is_array($tree) || ! isset($tree['tree']) || ! is_array($tree['tree'])) {
            throw new RuntimeException('Invalid response structure from GitHub Tree API');
        }

        /** @var array<string, mixed> $tree */
        if (($tree['truncated'] ?? false) === true) {
            Log::warning('GitHub tree response truncated (>100K entries). Some files may not be visible.', [
                'repo' => $this->repository->fullName(),
                'entries' => count($tree['tree']),
            ]);
        }

        /** @var array{tree: array<int, array<string, mixed>>, sha: string, url: string, truncated: bool} $tree */
        $this->cachedTree = $tree;

        return $tree;
    }

    protected function resolveSkillsPath(): string
    {
        if ($this->repository->path !== '') {
            return $this->repository->path;
        }

        $tree = $this->fetchRepositoryTree();

        if ($tree === null) {
            return '';
        }

        $treeItems = collect($tree['tree']);

        $rootDirs = $treeItems
            ->filter(fn (array $item): bool => $item['type'] === 'tree' && ! str_contains((string) $item['path'], '/'))
            ->pluck('path')
            ->toArray();

        if ($this->hasValidSkillsAtPath($treeItems, '', $rootDirs)) {
            return '';
        }

        foreach ($this->commonSkillPaths as $commonPath) {
            $topLevel = explode('/', $commonPath)[0];

            if (! in_array($topLevel, $rootDirs, true)) {
                continue;
            }

            $pathExists = $treeItems->contains(
                fn (array $item): bool => $item['path'] === $commonPath && $item['type'] === 'tree'
            );

            if (! $pathExists) {
                continue;
            }

            $dirsAtPath = $treeItems
                ->filter(fn (array $item): bool => $this->isDirectChildOf($item, $commonPath, 'tree'))
                ->map(fn (array $item): string => basename((string) $item['path']))
                ->toArray();

            if ($this->hasValidSkillsAtPath($treeItems, $commonPath, $dirsAtPath)) {
                return $commonPath;
            }
        }

        return '';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $treeItems
     * @param  array<int, string>  $dirNames
     */
    protected function hasValidSkillsAtPath(Collection $treeItems, string $basePath, array $dirNames): bool
    {
        $prefix = $basePath === '' ? '' : $basePath.'/';

        return collect($dirNames)->contains(function (string $dirName) use ($treeItems, $prefix): bool {
            $skillMdPath = $prefix.$dirName.'/SKILL.md';

            return $treeItems->contains(
                fn (array $item): bool => $item['path'] === $skillMdPath && $item['type'] === 'blob'
            );
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return Collection<int, array{name: string, path: string, type: string}>
     */
    protected function findSkillDirectoriesInTree(array $tree, string $basePath): Collection
    {
        return collect($tree)
            ->filter(fn (array $item): bool => $this->isDirectChildOf($item, $basePath, 'tree'))
            ->map(fn (array $item): array => [
                'name' => basename((string) $item['path']),
                'path' => $item['path'],
                'type' => 'dir',
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function isDirectChildOf(array $item, string $basePath, string $type): bool
    {
        if ($item['type'] !== $type) {
            return false;
        }

        $path = (string) $item['path'];

        if ($basePath === '') {
            return ! str_contains($path, '/');
        }

        $expectedPrefix = $basePath.'/';

        if (! str_starts_with($path, $expectedPrefix)) {
            return false;
        }

        $remainder = substr($path, strlen($expectedPrefix));

        return ! str_contains($remainder, '/');
    }

    /**
     * @param  Collection<int, array{name: string, path: string, type: string}>  $directories
     * @param  array<int, array<string, mixed>>  $tree
     * @return Collection<int, array{name: string, path: string, type: string}>
     */
    protected function validateSkillDirectories(Collection $directories, array $tree): Collection
    {
        $treeCollection = collect($tree);

        return $directories->filter(function (array $dir) use ($treeCollection): bool {
            $skillMdPath = $dir['path'].'/SKILL.md';

            return $treeCollection->contains(
                fn (array $item): bool => $item['path'] === $skillMdPath && $item['type'] === 'blob'
            );
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return Collection<int, array<string, mixed>>
     */
    protected function extractSkillFilesFromTree(array $tree, string $skillPath): Collection
    {
        $prefix = $skillPath.'/';

        return collect($tree)->filter(fn (array $item): bool => str_starts_with((string) $item['path'], $prefix))->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    protected function downloadFiles(array $files, string $targetPath, string $basePath): bool
    {
        $fileUrls = collect($files)->mapWithKeys(fn (array $item): array => [
            $item['path'] => $this->buildRawFileUrl($item['path']),
        ]);

        $responses = Http::pool(fn (Pool $pool) => $fileUrls->map(
            fn (string $url, string $path) => $pool->as($path)
                ->withHeaders(['User-Agent' => 'Laravel-Boost'])
                ->timeout(30)
                ->get($url)
        )->all());

        foreach ($files as $item) {
            $response = $responses[$item['path']] ?? null;

            if ($response === null || $response->failed()) {
                return false;
            }

            $relativePath = $this->getRelativePath($item['path'], $basePath);
            $localPath = $targetPath.'/'.$relativePath;

            if (! $this->ensureDirectoryExists(dirname($localPath))) {
                return false;
            }

            if (file_put_contents($localPath, $response->body()) === false) {
                return false;
            }
        }

        return true;
    }

    protected function buildRawFileUrl(string $path): string
    {
        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            $this->repository->owner,
            $this->repository->repo,
            $this->defaultBranch,
            ltrim($path, '/')
        );
    }

    protected function getRelativePath(string $fullPath, string $basePath): string
    {
        if (str_starts_with($fullPath, $basePath.'/')) {
            return substr($fullPath, strlen($basePath.'/'));
        }

        return basename($fullPath);
    }

    protected function ensureDirectoryExists(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0755, true);
    }

    protected function client(int $timeout = 30): PendingRequest
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-Boost',
        ];

        $token = $this->getGitHubToken();

        if ($token !== null) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return Http::withHeaders($headers)->timeout($timeout);
    }

    protected function getGitHubToken(): ?string
    {
        return config('boost.github.token') ?? config('services.github.token');
    }
}
