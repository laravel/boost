<?php

declare(strict_types=1);

namespace Laravel\Boost\DocsIndex;

use RuntimeException;
use Symfony\Component\Process\Process;

class DocsDownloader
{
    /**
     * Clone a repo with sparse-checkout for specific path(s).
     *
     * @param  string|list<string>  $sparsePaths
     */
    public function download(string $repo, string $branch, string|array $sparsePaths, string $targetDir): void
    {
        $repoUrl = "https://github.com/{$repo}.git";
        $paths = is_array($sparsePaths) ? $sparsePaths : [$sparsePaths];

        if ($paths === ['/']) {
            $this->run(
                ['git', 'clone', '--depth=1', '--single-branch', '--branch', $branch, $repoUrl, $targetDir],
                base_path()
            );
        } else {
            $this->run(
                ['git', 'clone', '--filter=blob:none', '--no-checkout', '--depth=1', '--single-branch', '--branch', $branch, $repoUrl, $targetDir],
                base_path()
            );

            $this->run(
                array_merge(['git', 'sparse-checkout', 'set'], $paths),
                base_path($targetDir)
            );

            $this->run(
                ['git', 'checkout'],
                base_path($targetDir)
            );
        }
    }

    /**
     * Pull latest changes for an existing clone.
     */
    public function update(string $targetDir): void
    {
        $this->run(
            ['git', 'pull', '--depth=1'],
            base_path($targetDir)
        );
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, string $cwd): void
    {
        $process = new Process($command, $cwd);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                sprintf('Command failed: %s — %s', implode(' ', $command), $process->getErrorOutput())
            );
        }
    }
}
