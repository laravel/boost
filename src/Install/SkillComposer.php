<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Exception;
use Illuminate\Support\Collection;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Roster;
use Symfony\Component\Yaml\Yaml;

class SkillComposer
{
    use RendersBladeGuidelines;

    /** @var Collection<string, Skill>|null */
    protected ?Collection $skills = null;

    protected string $userSkillsPath = '.ai/skills';

    public function __construct(
        protected Roster $roster,
        protected GuidelineConfig $config = new GuidelineConfig
    ) {}

    public function config(GuidelineConfig $config): self
    {
        $this->config = $config;
        $this->skills = null;

        return $this;
    }

    /**
     * Get all discovered skills (Boost built-in, third-party, and user).
     *
     * @return Collection<string, Skill>
     */
    public function skills(): Collection
    {
        if ($this->skills instanceof \Illuminate\Support\Collection) {
            return $this->skills;
        }

        return $this->skills = collect()
            ->merge($this->getBoostSkills())
            ->merge($this->getThirdPartySkills())
            ->merge($this->getUserSkills());
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getBoostSkills(): Collection
    {
        $aiPath = __DIR__.'/../../.ai/';

        /** @var Collection<int, array{path: string, name: string, version: string}> $packages */
        $packages = $this->roster->packages()
            ->map(function ($package) use ($aiPath): array {
                $packageDirName = str_replace('_', '-', strtolower($package->name()));

                return [
                    'path' => $aiPath.$packageDirName,
                    'name' => $packageDirName,
                    'version' => $package->majorVersion(),
                ];
            })
            ->collect();

        return $packages
            ->filter(fn (array $pkg): bool => is_dir($pkg['path']))
            ->flatMap(fn (array $pkg): Collection => $this->discoverSkillsFromPath(
                $pkg['path'],
                $pkg['name'],
                $pkg['version']
            ));
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverSkillsFromPath(string $packagePath, string $packageName, ?string $installedVersion): Collection
    {
        $versionSpecificSkills = $installedVersion !== null
            ? $this->discoverSkillsFromDirectory(
                $packagePath.'/'.$installedVersion.'/skill',
                $packageName
            )
            : collect();

        $rootSkills = $this->discoverSkillsFromDirectory(
            $packagePath.'/skill',
            $packageName
        );

        return $rootSkills->merge($versionSpecificSkills);
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverSkillsFromDirectory(string $skillPath, string $packageName): Collection
    {
        if (! is_dir($skillPath)) {
            return collect();
        }

        return collect(glob($skillPath.'/*', GLOB_ONLYDIR))
            ->map(fn (string $skillDir): ?Skill => $this->parseSkill($skillDir, $packageName))
            ->filter()
            ->keyBy(fn (Skill $skill): string => $skill->name);
    }

    protected function isVersionDirectory(string $dirName): bool
    {
        return preg_match('/^\d+(\.\d+)?$/', $dirName) === 1;
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getThirdPartySkills(): Collection
    {
        return collect(Composer::packagesDirectoriesWithBoostSkills())
            ->flatMap(fn (string $path, string $package): Collection => $this->discoverSkillsFromPath(
                $path,
                $package,
                $this->getPackageMajorVersion($package)
            ));
    }

    protected function getPackageMajorVersion(string $composerName): ?string
    {
        $package = $this->roster->packages()->first(fn ($pkg): bool => $pkg->rawName() === $composerName);

        return $package?->majorVersion();
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getUserSkills(): Collection
    {
        $userSkillsPath = base_path($this->userSkillsPath);

        if (! is_dir($userSkillsPath)) {
            return collect();
        }

        return collect(glob($userSkillsPath.'/*', GLOB_ONLYDIR))
            ->map(fn (string $skillPath): ?Skill => $this->parseSkill($skillPath, 'user', custom: true))
            ->filter()
            ->keyBy(fn (Skill $skill): string => $skill->name);
    }

    protected function parseSkill(string $skillPath, string $package = '', bool $custom = false): ?Skill
    {
        $skillFile = $this->findSkillFile($skillPath);
        if ($skillFile === null) {
            return null;
        }

        $content = file_get_contents($skillFile);
        $frontmatter = $this->parseSkillFrontmatter($content);

        if (empty($frontmatter['name']) || empty($frontmatter['description'])) {
            return null;
        }

        if ($package === '') {
            $package = $this->determinePackageFromPath($skillPath);
        }

        return new Skill(
            name: $frontmatter['name'],
            package: $package,
            path: $skillPath,
            description: $frontmatter['description'],
            custom: $custom,
        );
    }

    protected function findSkillFile(string $skillPath): ?string
    {
        foreach (['SKILL.blade.php', 'SKILL.md'] as $filename) {
            $path = $skillPath.'/'.$filename;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function determinePackageFromPath(string $skillPath): string
    {
        $parentDir = basename(dirname($skillPath));

        return $this->isVersionDirectory($parentDir)
            ? basename(dirname($skillPath, 2))
            : $parentDir;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseSkillFrontmatter(string $content): array
    {
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            return [];
        }

        try {
            return Yaml::parse($matches[1]) ?? [];
        } catch (Exception) {
            return [];
        }
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return new GuidelineAssist($this->roster, $this->config, $this->skills());
    }
}
