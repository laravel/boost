<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GitHubSkillProvider
{
    protected ?string $defaultBranch = null;

    /** @var array<string, mixed>|null */
    protected ?array $cachedTree = null;

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
            ->filter(fn (array $item): bool => $item['type'] === 'blob' && in_array(basename((string) $item['path']), ['SKILL.md', 'SKILL.blade.php'], true));

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
            urlencode($this->resolveDefaultBranch())
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

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    protected function downloadFiles(array $files, string $targetPath, string $basePath): bool
    {
        $fileUrls = collect($files)->mapWithKeys(fn (array $item): array => [
            $item['path'] => $this->buildRawFileUrl($item['path']),
        ]);

        try {
            $responses = Http::pool(fn (Pool $pool) => $fileUrls->map(
                fn (string $url, string $path) => $pool->as($path)
                    ->timeout(60)
                    ->get($url)
            )->all(), concurrency: 25);
        } catch (Throwable) {
            return false;
        }

        foreach ($files as $item) {
            $response = $responses[$item['path']] ?? null;

            if ($response instanceof Throwable || $response === null || $response->failed()) {
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
            $this->resolveDefaultBranch(),
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

    protected function resolveDefaultBranch(): string
    {
        if ($this->defaultBranch !== null) {
            return $this->defaultBranch;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s',
            $this->repository->owner,
            $this->repository->repo
        );

        $response = $this->client(timeout: 15)->get($url);

        $branch = $response->successful()
            ? $response->json('default_branch')
            : null;

        $this->defaultBranch = is_string($branch) ? $branch : 'main';

        return $this->defaultBranch;
    }

    protected function getGitHubToken(): ?string
    {
        return config('boost.github.token') ?? config('services.github.token');
    }
}
