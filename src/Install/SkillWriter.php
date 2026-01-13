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

        if (! is_dir($target) && ! @mkdir($target, 0755, true)) {
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
        $targetFileDir = dirname($targetFile);

        if (! is_dir($targetFileDir) && ! @mkdir($targetFileDir, 0755, true)) {
            return false;
        }

        if (str_ends_with($relativePath, '.blade.php')) {
            $renderedContent = $this->renderBladeSkillFile($file->getRealPath());
            $targetFile = preg_replace('/\.blade\.php$/', '.md', $targetFile);

            return file_put_contents($targetFile, $renderedContent) !== false;
        }

        return @copy($file->getRealPath(), $targetFile);
    }

    /**
     * Render a Blade skill file to Markdown.
     */
    protected function renderBladeSkillFile(string $path): string
    {
        $content = file_get_contents($path);
        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $path);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = [];

        return trim($rendered);
    }
}
