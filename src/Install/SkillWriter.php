<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SupportSkills;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SkillWriter
{
    use RendersBladeGuidelines;

    public const SUCCESS = 0;

    public const UPDATED = 1;

    public const FAILED = 2;

    public function __construct(protected SupportSkills $agent) {}

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
        if ($isBladeFile) {
            $renderedContent = trim($this->renderBladeFile($file->getRealPath()));
            $targetFile = preg_replace('/\.blade\.php$/', '.md', $targetFile);

            return file_put_contents($targetFile, $renderedContent) !== false;
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
