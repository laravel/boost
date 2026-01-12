<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SkillsAgent;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class SkillWriter
{
    public const SUCCESS = 0;

    public const UPDATED = 1;

    public const FAILED = 2;

    public function __construct(protected SkillsAgent $agent) {}

    public function write(Skill $skill): int
    {
        $targetPath = base_path($this->agent->skillsPath().'/'.$skill->name);

        $existed = is_dir($targetPath);

        if (! $this->copyDirectory($skill->path, $targetPath)) {
            return self::FAILED;
        }

        return $existed ? self::UPDATED : self::SUCCESS;
    }

    /**
     * @param  Collection<string, Skill>  $skills
     * @return array<string, int>
     */
    public function writeAll(Collection $skills): array
    {
        $results = [];

        foreach ($skills as $skill) {
            $results[$skill->name] = $this->write($skill);
        }

        return $results;
    }

    protected function copyDirectory(string $source, string $target): bool
    {
        if (! is_dir($source)) {
            return false;
        }

        if (! is_dir($target) && ! @mkdir($target, 0755, true)) {
            throw new RuntimeException("Failed to create directory: {$target}");
        }

        $finder = Finder::create()
            ->files()
            ->in($source)
            ->ignoreDotFiles(false);

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $targetFile = $target.'/'.$relativePath;
            $targetDir = dirname($targetFile);

            if (! is_dir($targetDir) && ! @mkdir($targetDir, 0755, true)) {
                return false;
            }

            if (! @copy($file->getRealPath(), $targetFile)) {
                return false;
            }
        }

        return true;
    }
}
