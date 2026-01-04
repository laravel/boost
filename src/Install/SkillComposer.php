<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Discovers and composes Agent Skills from skill.blade.php files.
 *
 * Mirrors GuidelineComposer's discovery patterns but for skills.
 */
class SkillComposer
{
    use RendersBladeGuidelines;

    protected string $userSkillDir = '.ai/guidelines';

    /** @var Collection<string, Skill> */
    protected Collection $skills;

    protected GuidelineConfig $config;

    /**
     * Keys that should stay in the foundation file, not become skills.
     */
    protected array $foundationKeys = ['foundation', 'boost'];

    public function __construct(
        protected Roster $roster,
        protected GuidelineComposer $guidelineComposer,
    ) {
        $this->config = new GuidelineConfig;
    }

    public function config(GuidelineConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function customSkillPath(string $path = ''): string
    {
        return base_path($this->userSkillDir.'/'.ltrim($path, '/'));
    }

    /**
     * Compose all skills from skill.blade.php files.
     *
     * @return Collection<string, Skill>
     */
    public function compose(): Collection
    {
        if (! empty($this->skills)) {
            return $this->skills;
        }

        $base = collect()
            ->merge($this->getCoreSkills())
            ->merge($this->getPackageSkills())
            ->merge($this->getThirdPartySkills());

        $basePaths = $base->map(fn (Skill $skill) => $skill->path)->filter()->values();

        $customSkills = $this->getUserSkills()
            ->reject(fn (Skill $skill): bool => $basePaths->contains($skill->path));

        return $this->skills = $customSkills
            ->merge($base)
            ->filter();
    }

    /**
     * Compose the foundation content that stays in the base file.
     *
     * Includes foundation guidelines plus any guidelines that don't have
     * a corresponding skill.blade.php file (e.g., third-party packages).
     */
    public function composeFoundation(): string
    {
        $guidelines = $this->guidelineComposer->config($this->config)->guidelines();
        $skillNames = $this->compose()->keys()->all();

        return GuidelineComposer::composeGuidelines(
            $guidelines->filter(fn ($guideline, $key) => $this->shouldIncludeInFoundation($key, $skillNames))
        );
    }

    /**
     * Check if a guideline should be included in the foundation.
     *
     * @param  array<string>  $skillNames
     */
    protected function shouldIncludeInFoundation(string $key, array $skillNames): bool
    {
        // Always include foundation keys
        if ($this->isFoundationGuideline($key)) {
            return true;
        }

        // Include if there's no corresponding skill
        $expectedSkillName = $this->guidelineKeyToSkillName($key);

        return ! in_array($expectedSkillName, $skillNames, true);
    }

    /**
     * Convert a guideline key to the expected skill name.
     */
    protected function guidelineKeyToSkillName(string $key): string
    {
        $name = str_replace(['/', '.'], '-', $key);
        $name = preg_replace('/-+/', '-', $name);

        return 'boost-'.trim($name, '-');
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getUserSkills(): Collection
    {
        return collect($this->skillsDir($this->customSkillPath()))
            ->filter()
            ->keyBy(fn (Skill $skill) => $skill->name);
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getCoreSkills(): Collection
    {
        return collect([
            $this->skill('php/skill'),
        ])
            ->filter()
            ->keyBy(fn (Skill $skill) => $skill->name);
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getPackageSkills(): Collection
    {
        return collect($this->roster->packages())
            ->flatMap(function (Package $package): Collection {
                $skillDir = str_replace('_', '-', strtolower($package->name()));
                $skills = collect();

                // Base skill for package
                $baseSkill = $this->skill($skillDir.'/skill');
                if ($baseSkill) {
                    $skills->put($baseSkill->name, $baseSkill);
                }

                // Version-specific skills
                $versionSkills = $this->skillsDir($skillDir.'/'.$package->majorVersion());
                foreach ($versionSkills as $skill) {
                    if ($skill) {
                        $skills->put($skill->name, $skill);
                    }
                }

                return $skills;
            });
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getThirdPartySkills(): Collection
    {
        $skills = collect();

        collect(Composer::packagesDirectoriesWithBoostGuidelines())
            ->each(function (string $path) use ($skills): void {
                $packageSkills = $this->skillsDir($path, true);

                foreach ($packageSkills as $skill) {
                    if ($skill) {
                        $skills->put($skill->name, $skill);
                    }
                }
            });

        return $skills;
    }

    /**
     * @return array<Skill|null>
     */
    protected function skillsDir(string $dirPath, bool $thirdParty = false): array
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, dirname(__DIR__, 2).'/.ai/'.$dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->name('skill.blade.php');
        } catch (DirectoryNotFoundException) {
            return [];
        }

        return collect($finder)
            ->map(fn (SplFileInfo $file): ?Skill => $this->loadSkillFromPath($file->getRealPath(), $thirdParty))
            ->filter()
            ->all();
    }

    /**
     * Load a skill from a relative path.
     */
    protected function skill(string $path, bool $thirdParty = false): ?Skill
    {
        $path = $this->skillPath($path);

        if (is_null($path)) {
            return null;
        }

        return $this->loadSkillFromPath($path, $thirdParty);
    }

    /**
     * Load a skill from an absolute path.
     */
    protected function loadSkillFromPath(string $path, bool $thirdParty = false): ?Skill
    {
        if (! file_exists($path)) {
            return null;
        }

        $content = (string) file_get_contents($path);

        $frontmatter = $this->extractFrontmatter($content);
        if (empty($frontmatter['name']) || empty($frontmatter['description'])) {
            return null;
        }

        $bodyContent = $this->removeFrontmatter($content);
        $rendered = trim($this->renderSkillContent($bodyContent, $path));

        if (empty($rendered)) {
            return null;
        }

        return new Skill(
            name: $frontmatter['name'],
            description: $frontmatter['description'],
            content: $rendered,
            metadata: $frontmatter['metadata'] ?? [],
            path: $path,
            custom: str_contains($path, $this->customSkillPath()),
            thirdParty: $thirdParty,
        );
    }

    /**
     * Extract YAML frontmatter from content.
     */
    protected function extractFrontmatter(string $content): array
    {
        if (! preg_match('/^---\n(.+?)\n---\n/s', $content, $matches)) {
            return [];
        }

        try {
            return Yaml::parse($matches[1]) ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Remove YAML frontmatter from content.
     */
    protected function removeFrontmatter(string $content): string
    {
        return preg_replace('/^---\n.+?\n---\n/s', '', $content) ?? $content;
    }

    /**
     * Render a skill.blade.php file content.
     */
    protected function renderSkillContent(string $content, string $path): string
    {
        $content = $this->processBoostSnippets($content);
        $rendered = $this->renderContent($content, $path);
        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);
        $this->storedSnippets = [];

        return $rendered;
    }

    protected function prependPackageSkillPath(string $path): string
    {
        return $this->prependSkillPath($path, dirname(__DIR__, 2).'/.ai/');
    }

    protected function prependUserSkillPath(string $path): string
    {
        return $this->prependSkillPath($path, $this->customSkillPath());
    }

    private function prependSkillPath(string $path, string $basePath): string
    {
        if (! str_ends_with($path, '.blade.php')) {
            $path .= '.blade.php';
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $basePath.$path);
    }

    protected function skillPath(string $path): ?string
    {
        // Relative path, prepend our package path to it
        if (! file_exists($path)) {
            $path = $this->prependPackageSkillPath($path);
            if (! file_exists($path)) {
                return null;
            }
        }

        $realPath = realpath($path);
        if ($realPath === false) {
            return null;
        }

        // If this is a custom skill, return it unchanged
        if (str_contains($realPath, $this->customSkillPath())) {
            return $realPath;
        }

        // Check if the user has an override for this skill
        $basePath = (string) realpath(dirname(__DIR__, 2));
        $relativePath = str_replace([$basePath, '.ai'.DIRECTORY_SEPARATOR, '.ai/'], '', $realPath);
        $relativePath = ltrim($relativePath, '/\\');

        $customPath = $this->prependUserSkillPath($relativePath);

        return file_exists($customPath) ? $customPath : $realPath;
    }

    /**
     * Check if a guideline key should stay in foundation.
     */
    protected function isFoundationGuideline(string $key): bool
    {
        return in_array($key, $this->foundationKeys, true);
    }
}
