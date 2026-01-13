<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SkillsAgent;
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

    public function __construct(protected SkillsAgent $agent) {}

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
            $renderedContent = $this->renderBladeSkillFile($file->getRealPath());
            $targetFile = preg_replace('/\.blade\.php$/', '.md', $targetFile);

            return file_put_contents($targetFile, $renderedContent) !== false;
        }

        return @copy($file->getRealPath(), $targetFile);
    }

    protected function ensureDirectoryExists(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0755, true);
    }

    /**
     * Validate skill name to prevent path traversal attacks.
     */
    protected function isValidSkillName(string $name): bool
    {
        // Reject names containing path traversal characters
        if (str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        // Reject empty names or names with only whitespace
        if (trim($name) === '') {
            return false;
        }

        return true;
    }

    /**
     * Render a Blade skill file to Markdown.
     */
    protected function renderBladeSkillFile(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read skill file: {$path}");
        }

        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $path);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = [];

        return trim($rendered);
    }
}
