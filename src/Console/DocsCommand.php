<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\DocsIndex\DocsDownloader;
use Laravel\Boost\DocsIndex\DocsIndexInjector;
use Laravel\Boost\DocsIndex\DocsRegistry;
use Laravel\Boost\DocsIndex\IndexGenerator;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Support\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand('boost:docs', 'Download package docs locally and generate index in guidelines file')]
class DocsCommand extends Command
{
    protected $signature = 'boost:docs
        {--force : Force re-clone even if docs already exist}
        {--package=* : Only process specific packages}';

    private const OUTPUT_DIR = '.laravel-docs';

    public function handle(
        Config $config,
        AgentsDetector $agentsDetector,
        DocsDownloader $downloader,
        IndexGenerator $indexer,
        DocsIndexInjector $injector,
    ): int {
        $installedPackages = $this->getInstalledPackages();
        $packageFilter = $this->option('package');
        $repoPaths = [];

        // Process symlinks
        foreach (DocsRegistry::symlinks() as $name => $symlinkConfig) {
            if (! isset($installedPackages[$symlinkConfig['package']])) {
                $this->line("  <comment>Skipping {$name}</comment> — {$symlinkConfig['package']} not installed");

                continue;
            }

            $sourcePath = base_path($symlinkConfig['source']);
            $linkPath = base_path(self::OUTPUT_DIR.'/'.$name);

            if (! is_dir($sourcePath)) {
                $this->line("  <comment>Skipping {$name}</comment> — source path missing");

                continue;
            }

            if (is_link($linkPath)) {
                $this->line("  <info>{$name}</info> → symlink exists");
            } elseif (is_dir($linkPath)) {
                $this->line("  <info>{$name}</info> → directory exists (not a symlink)");
            } else {
                if (! is_dir(dirname($linkPath))) {
                    @mkdir(dirname($linkPath), 0755, true);
                }

                symlink($sourcePath, $linkPath);
                $this->line("  <info>{$name}</info> → symlinked");
            }

            $repoPaths[$name] = '/';
        }

        // Process repos
        $repos = DocsRegistry::installedRepos($installedPackages);

        foreach ($repos as $key => $repoConfig) {
            if (! empty($packageFilter) && ! in_array($key, $packageFilter, true)) {
                continue;
            }

            $version = $installedPackages[$repoConfig['version_from']];
            $majorVersion = explode('.', $version)[0];
            $branch = str_replace('{major}', $majorVersion, $repoConfig['branch']);
            $repo = $repoConfig['repo'];
            $sparsePath = $repoConfig['path'];

            $repoSubDir = str_replace('/', '-', $repo);
            $targetSubDir = self::OUTPUT_DIR.'/'.$repoSubDir;

            $repoPaths[$repoSubDir] = $sparsePath;

            $this->line("  <info>{$key}</info> → {$repo}@{$branch}");

            try {
                if (is_dir(base_path($targetSubDir)) && ! $this->option('force')) {
                    $downloader->update($targetSubDir);
                    $this->line('    Updated (git pull)');
                } else {
                    if (is_dir(base_path($targetSubDir))) {
                        $this->deleteDirectory(base_path($targetSubDir));
                    }

                    $downloader->download($repo, $branch, $sparsePath, $targetSubDir);
                    $this->line('    Cloned');
                }
            } catch (Throwable $e) {
                $this->error("    Failed: {$e->getMessage()}");

                continue;
            }
        }

        // Generate index
        $this->newLine();
        $this->line('Generating index...');
        $index = $indexer->generate(self::OUTPUT_DIR, $repoPaths);

        if (empty($index)) {
            $this->warn('No docs found — skipping injection.');

            return self::SUCCESS;
        }

        // Inject into each agent's guidelines file
        $agents = $this->resolveGuidelinesAgents($config, $agentsDetector);

        foreach ($agents as $agent) {
            $filePath = $agent->guidelinesPath();
            $injector->inject($filePath, $index);
            $this->line("Injected index into <info>{$filePath}</info>");
        }

        $this->ensureGitignore();

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Agent&SupportsGuidelines>
     */
    private function resolveGuidelinesAgents(Config $config, AgentsDetector $agentsDetector): Collection
    {
        $configuredAgentNames = $config->getAgents();

        if (empty($configuredAgentNames)) {
            return collect();
        }

        return $agentsDetector->getAgents()
            ->filter(fn (Agent $agent): bool => $agent instanceof SupportsGuidelines
                && in_array($agent->name(), $configuredAgentNames, true)
            )
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function getInstalledPackages(): array
    {
        $lockPath = base_path('composer.lock');

        if (! file_exists($lockPath)) {
            return [];
        }

        $lock = json_decode(file_get_contents($lockPath), true);
        $packages = [];

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $pkg) {
            $packages[$pkg['name']] = ltrim($pkg['version'], 'v');
        }

        return $packages;
    }

    private function ensureGitignore(): void
    {
        $gitignorePath = base_path('.gitignore');
        $entry = '/'.self::OUTPUT_DIR;

        if (! file_exists($gitignorePath)) {
            return;
        }

        $content = file_get_contents($gitignorePath);

        if (str_contains($content, $entry)) {
            return;
        }

        file_put_contents($gitignorePath, "\n# Local docs for AI agents\n{$entry}\n", FILE_APPEND);
        $this->line("Added <info>{$entry}</info> to .gitignore");
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        @rmdir($dir);
    }
}
