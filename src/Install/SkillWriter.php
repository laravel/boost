<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use FilesystemIterator;
use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Contracts\SupportsSkills;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SkillWriter
{
    use RendersBladeGuidelines;

    public const SUCCESS = 0;

    public const UPDATED = 1;

    public const FAILED = 2;

    public function __construct(protected SupportsSkills $agent)
    {
        //
    }

    public function write(Skill $skill): int
    {
        if (! $this->isValidSkillName($skill->name)) {
            throw new RuntimeException("Invalid skill name: {$skill->name}");
        }

        $targetPath = base_path($this->agent->skillsPath().DIRECTORY_SEPARATOR.$skill->name);
        $canonicalPath = base_path('.ai'.DIRECTORY_SEPARATOR.'skills'.DIRECTORY_SEPARATOR.$skill->name);
        $existed = $this->pathExists($targetPath);

        if (! $skill->custom) {
            return $this->writeNonCustomSkill($skill, $targetPath, $canonicalPath, $existed);
        }

        return $this->writeCustomSkill($skill, $targetPath, $canonicalPath, $existed);
    }

    protected function writeNonCustomSkill(Skill $skill, string $targetPath, string $canonicalPath, bool $existed): int
    {
        $canonicalExists = $this->pathExists($canonicalPath);
        $needsCanonicalUpdate = $canonicalExists && ! $this->pathsMatch($skill->path, $canonicalPath);

        if ($needsCanonicalUpdate && ! $this->copyDirectory($skill->path, $canonicalPath)) {
            return self::FAILED;
        }

        if (! $this->copyDirectory($skill->path, $targetPath)) {
            return self::FAILED;
        }

        return $existed ? self::UPDATED : self::SUCCESS;
    }

    protected function writeCustomSkill(Skill $skill, string $targetPath, string $canonicalPath, bool $existed): int
    {
        if (! $this->pathsMatch($skill->path, $canonicalPath) && ! $this->copyDirectory($skill->path, $canonicalPath)) {
            return self::FAILED;
        }

        if (! $this->ensureDirectoryExists(dirname($targetPath))) {
            return self::FAILED;
        }

        if (! $this->createSymlink($canonicalPath, $targetPath) && ! $this->copyDirectory($skill->path, $targetPath)) {
            return self::FAILED;
        }

        return $existed ? self::UPDATED : self::SUCCESS;
    }

    protected function pathExists(string $path): bool
    {
        return is_dir($path) || is_link($path);
    }

    /**
     * @param  Collection<string, Skill>  $skills
     * @return array<string, int>
     */
    public function writeAll(Collection $skills): array
    {
        return $skills
            ->mapWithKeys(fn (Skill $skill): array => [$skill->name => $this->write($skill)])
            ->all();
    }

    /**
     * @param  Collection<string, Skill>  $skills
     * @param  array<int, string>  $previouslyTrackedSkills
     * @return array<string, int>
     */
    public function sync(Collection $skills, array $previouslyTrackedSkills = []): array
    {
        $written = $this->writeAll($skills);

        $newSkillNames = $skills->keys()->all();

        $staleSkillNames = array_values(array_diff($previouslyTrackedSkills, $newSkillNames));

        $this->removeStale($staleSkillNames);

        return $written;
    }

    public function remove(string $skillName): bool
    {
        if (! $this->isValidSkillName($skillName)) {
            return false;
        }

        $targetPath = base_path($this->agent->skillsPath().DIRECTORY_SEPARATOR.$skillName);

        if (! $this->pathExists($targetPath)) {
            return true;
        }

        return $this->deleteDirectory($targetPath);
    }

    /**
     * @param  array<int, string>  $skillNames
     * @return array<string, bool>
     */
    public function removeStale(array $skillNames): array
    {
        $results = [];

        foreach ($skillNames as $name) {
            $results[$name] = $this->remove($name);
        }

        return $results;
    }

    protected function deleteDirectory(string $path): bool
    {
        if (is_link($path)) {
            if (@unlink($path)) {
                return true;
            }

            // On Windows, directory symlinks can require rmdir instead of unlink,
            // even when the symlink target no longer exists (dangling symlinks).
            if (@rmdir($path)) {
                return true;
            }

            return ! file_exists($path) && ! is_link($path);
        }

        if (is_file($path)) {
            return @unlink($path);
        }

        if (! is_dir($path)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isLink()) {
                $linkPath = $file->getPathname();

                if (! @unlink($linkPath) && is_dir($linkPath)) {
                    @rmdir($linkPath);
                }

                continue;
            }

            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        return @rmdir($path) || ! is_dir($path);
    }

    protected function copyDirectory(string $source, string $target): bool
    {
        if (! is_dir($source)) {
            return false;
        }

        $this->deleteDirectory($target);

        if (! $this->ensureDirectoryExists($target)) {
            throw new RuntimeException("Failed to create directory: {$target}");
        }

        $finder = Finder::create()
            ->files()
            ->in($source)
            ->ignoreDotFiles(false);

        foreach ($finder as $file) {
            if (! $this->copyFile($file, $target)) {
                return false;
            }
        }

        return true;
    }

    protected function copyFile(SplFileInfo $file, string $targetDir): bool
    {
        $relativePath = $file->getRelativePathname();
        $targetFile = $targetDir.DIRECTORY_SEPARATOR.$relativePath;

        if (! $this->ensureDirectoryExists(dirname($targetFile))) {
            return false;
        }

        $isBladeFile = str_ends_with($relativePath, '.blade.php');
        $isMarkdownFile = str_ends_with($relativePath, '.md');

        if ($isBladeFile) {
            $content = MarkdownFormatter::format(trim($this->renderBladeFile($file->getRealPath())));
            $replacedTargetFile = preg_replace('/\.blade\.php$/', '.md', $targetFile);

            if ($replacedTargetFile === null) {
                $replacedTargetFile = substr($targetFile, 0, -10).'.md';
            }

            return file_put_contents($replacedTargetFile, $content) !== false;
        }

        if ($isMarkdownFile) {
            $content = MarkdownFormatter::format(trim(file_get_contents($file->getRealPath())));

            return file_put_contents($targetFile, $content) !== false;
        }

        return @copy($file->getRealPath(), $targetFile);
    }

    protected function ensureDirectoryExists(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0755, true);
    }

    protected function createSymlink(string $target, string $link): bool
    {
        $resolvedTarget = realpath($target) ?: $target;
        $resolvedLink = realpath($link) ?: $link;

        if ($this->pathsMatch($resolvedTarget, $resolvedLink)) {
            return true;
        }

        if (file_exists($link) || is_link($link)) {
            $this->deleteDirectory($link);
        }

        if (! $this->ensureDirectoryExists(dirname($link))) {
            return false;
        }

        return @symlink($this->relativePath($resolvedTarget, dirname($link)), $link);
    }

    protected function pathsMatch(string $left, string $right): bool
    {
        $resolvedLeft = realpath($left) ?: $left;
        $resolvedRight = realpath($right) ?: $right;

        return rtrim($resolvedLeft, DIRECTORY_SEPARATOR) === rtrim($resolvedRight, DIRECTORY_SEPARATOR);
    }

    protected function relativePath(string $target, string $from): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $resolvedTarget = str_replace('\\', '/', realpath($target) ?: $target);
        $resolvedFrom = str_replace('\\', '/', realpath($from) ?: $from);

        if (! str_starts_with($resolvedTarget, $base.'/') || ! str_starts_with($resolvedFrom, $base.'/')) {
            return $resolvedTarget;
        }

        $targetRel = ltrim(substr($resolvedTarget, strlen($base)), '/');
        $fromRel = ltrim(substr($resolvedFrom, strlen($base)), '/');
        $depth = $fromRel === '' ? 0 : count(explode('/', $fromRel));

        return str_repeat('../', $depth).$targetRel;
    }

    protected function isValidSkillName(string $name): bool
    {
        $hasPathTraversal = str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\');

        return ! $hasPathTraversal && trim($name) !== '';
    }
}
