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

        $targetPath = base_path($this->agent->skillsPath().'/'.$skillName);

        if (! is_dir($targetPath)) {
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
        if (! is_dir($path)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
        }

        return @rmdir($path);
    }

    protected function copyDirectory(string $source, string $target): bool
    {
        if (! is_dir($source)) {
            return false;
        }

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
        $targetFile = $targetDir.'/'.$relativePath;

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

    protected function isValidSkillName(string $name): bool
    {
        $hasPathTraversal = str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\');

        return ! $hasPathTraversal && trim($name) !== '';
    }
}
