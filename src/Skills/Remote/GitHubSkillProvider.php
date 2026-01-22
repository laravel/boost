<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubSkillProvider
{
    protected string $defaultBranch = 'main';

    protected string $resolvedPath = '';

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
        $this->resolvedPath = $this->resolveSkillsPath();
        $contents = $this->fetchContents($this->resolvedPath);

        if ($contents === null) {
            return collect();
        }

        $directories = collect($contents)
            ->filter(fn (array $item): bool => $item['type'] === 'dir');

        if ($directories->isEmpty()) {
            return collect();
        }

        $skillMdUrls = $directories->mapWithKeys(fn (array $item): array => [
            $item['name'] => $this->buildRawFileUrl($item['path'].'/SKILL.md'),
        ]);

        $responses = Http::pool(fn (Pool $pool) => $skillMdUrls->map(
            fn (string $url, string $name) => $pool->as($name)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'Laravel-Boost'])
                ->timeout(30)
                ->get($url)
        )->all());

        return $directories
            ->filter(fn (array $item): bool => ($responses[$item['name']] ?? null)?->successful() ?? false)
            ->map(fn (array $item): RemoteSkill => new RemoteSkill(
                name: $item['name'],
                repo: $this->repository->fullName(),
                path: $item['path'],
            ))
            ->keyBy(fn (RemoteSkill $skill): string => $skill->name);
    }

    public function downloadSkill(RemoteSkill $skill, string $targetPath): bool
    {
        $contents = $this->fetchContents($skill->path);

        if ($contents === null) {
            return false;
        }

        if (! $this->ensureDirectoryExists($targetPath)) {
            return false;
        }

        return $this->downloadContentsRecursively($contents, $targetPath, $skill->path);
    }

    protected function resolveSkillsPath(): string
    {
        if ($this->repository->path !== '') {
            return $this->repository->path;
        }

        $rootContents = $this->fetchContents();

        if ($rootContents === null) {
            return '';
        }

        if ($this->hasValidSkillDirectories($rootContents)) {
            return '';
        }

        $rootDirectories = collect($rootContents)
            ->filter(fn (array $item): bool => $item['type'] === 'dir')
            ->pluck('name')
            ->toArray();

        foreach ($this->commonSkillPaths as $commonPath) {
            $topLevel = Str::before($commonPath, '/');

            if (! in_array($topLevel, $rootDirectories, true)) {
                continue;
            }

            $pathContents = $this->fetchContents($commonPath);

            if ($pathContents !== null && $this->hasValidSkillDirectories($pathContents)) {
                return $commonPath;
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     */
    protected function hasValidSkillDirectories(array $contents): bool
    {
        $directories = collect($contents)->filter(fn (array $item): bool => $item['type'] === 'dir');

        if ($directories->isEmpty()) {
            return false;
        }

        $urls = $directories->mapWithKeys(fn (array $dir): array => [
            $dir['name'] => $this->buildRawFileUrl($dir['path'].'/SKILL.md'),
        ]);

        $responses = Http::pool(fn (Pool $pool) => $urls->map(
            fn (string $url, string $name) => $pool->as($name)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'Laravel-Boost'])
                ->timeout(10)
                ->get($url)
        )->all());

        return collect($responses)->contains(fn ($response) => $response?->successful());
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function fetchContents(string $path = ''): ?array
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/contents/%s',
            $this->repository->owner,
            $this->repository->repo,
            ltrim($path, '/')
        );

        $response = $this->client()->get($url);

        if ($response->failed()) {
            return null;
        }

        $contents = $response->json();

        if (! is_array($contents)) {
            return null;
        }

        if (isset($contents['type'])) {
            return [$contents];
        }

        return $contents;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     */
    protected function downloadContentsRecursively(array $contents, string $targetPath, string $basePath): bool
    {
        [$files, $directories] = collect($contents)->partition(fn (array $item): bool => $item['type'] !== 'dir');

        if ($files->isNotEmpty() && ! $this->downloadFiles($files->toArray(), $targetPath, $basePath)) {
            return false;
        }

        foreach ($directories as $item) {
            $relativePath = $this->getRelativePath($item['path'], $basePath);
            $localPath = $targetPath.'/'.$relativePath;

            if (! $this->ensureDirectoryExists($localPath)) {
                return false;
            }

            $subContents = $this->fetchContents($item['path']);

            if ($subContents === null || ! $this->downloadContentsRecursively($subContents, $targetPath, $basePath)) {
                return false;
            }
        }

        return true;
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
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'Laravel-Boost'])
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
        if (Str::startsWith($fullPath, $basePath.'/')) {
            return Str::after($fullPath, $basePath.'/');
        }

        return basename($fullPath);
    }

    protected function ensureDirectoryExists(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0755, true);
    }

    protected function client(int $timeout = 30): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-Boost',
        ])->timeout($timeout);
    }
}
