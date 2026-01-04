<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Finder\Finder;

/**
 * Writes Agent Skills to the filesystem.
 */
class SkillWriter
{
    /**
     * Prefix used to identify Boost-generated skills.
     */
    protected const SKILL_PREFIX = 'boost-';

    public function __construct(
        protected string $skillsPath,
    ) {}

    /**
     * Write a single skill to disk.
     */
    public function write(Skill $skill): bool
    {
        $skillDir = $this->skillPath($skill->name);

        if (! is_dir($skillDir) && ! @mkdir($skillDir, 0755, true)) {
            throw new RuntimeException("Failed to create skill directory: {$skillDir}");
        }

        $skillMdPath = $skillDir.'/SKILL.md';
        $content = $skill->toSkillMd();

        if (file_put_contents($skillMdPath, $content) === false) {
            throw new RuntimeException("Failed to write skill file: {$skillMdPath}");
        }

        return true;
    }

    /**
     * Write multiple skills to disk.
     *
     * @param  Collection<string, Skill>  $skills
     * @return array<string, bool> Map of skill names to success status
     */
    public function writeAll(Collection $skills): array
    {
        $results = [];

        foreach ($skills as $skill) {
            $results[$skill->name] = $this->write($skill);
        }

        return $results;
    }

    /**
     * Remove all Boost-generated skills from the skills directory.
     *
     * Only removes skills with the 'boost-' prefix to avoid
     * accidentally deleting user-created skills.
     */
    public function cleanBoostSkills(): void
    {
        $basePath = base_path($this->skillsPath);

        if (! is_dir($basePath)) {
            return;
        }

        $finder = Finder::create()
            ->directories()
            ->in($basePath)
            ->depth(0)
            ->name(self::SKILL_PREFIX.'*');

        foreach ($finder as $dir) {
            $this->removeDirectory($dir->getRealPath());
        }
    }

    /**
     * Get the full path to a skill directory.
     */
    protected function skillPath(string $skillName): string
    {
        return base_path($this->skillsPath.'/'.$skillName);
    }

    /**
     * Recursively remove a directory and its contents.
     */
    protected function removeDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        return @rmdir($path);
    }
}
