<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Exception;
use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\Concerns\DiscoverPackagePaths;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;
use Symfony\Component\Yaml\Yaml;

class SkillComposer
{
    use DiscoverPackagePaths;
    use RendersBladeGuidelines;

    /** @var Collection<string, Skill>|null */
    protected ?Collection $skills = null;

    public function __construct(protected Roster $roster, protected GuidelineConfig $config = new GuidelineConfig)
    {
        //
    }

    protected function getRoster(): Roster
    {
        return $this->roster;
    }

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
        if ($this->skills instanceof Collection) {
            return $this->skills;
        }

        $excluded = config('boost.skills.exclude', []);

        return $this->skills = collect()
            ->merge($this->getBoostSkills())
            ->merge($this->getThirdPartySkills())
            ->reject(fn (Skill $skill, string $key): bool => in_array($key, $excluded, true))
            ->merge($this->getUserSkills());
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getBoostSkills(): Collection
    {
        /** @var Collection<string, Skill> $skills */
        $skills = $this->getRoster()->packages()
            ->reject(fn (Package $package): bool => $this->shouldExcludePackage($package))
            ->collect()
            ->flatMap(function (Package $package): Collection {
                $name = $this->normalizePackageName($package->name());

                $vendorSkillPath = $this->resolveFirstPartyBoostPath($package, 'skills');

                $vendorSkills = $vendorSkillPath !== null
                    ? $this->discoverSkillsFromDirectory($vendorSkillPath, $name)
                    : collect();

                $aiPath = $this->getBoostAiPath().DIRECTORY_SEPARATOR.$name;
                $aiSkills = is_dir($aiPath)
                    ? $this->discoverSkillsFromPath($aiPath, $name, $package->majorVersion())
                    : collect();

                return $aiSkills->merge($vendorSkills);
            });

        return $skills;
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getThirdPartySkills(): Collection
    {
        $skills = collect(Composer::packagesDirectoriesWithBoostSkills())
            ->reject(fn (string $path, string $package): bool => Composer::isFirstPartyPackage($package))
            ->flatMap(fn (string $path, string $package): Collection => $this->discoverSkillsFromDirectory($path, $package));

        $selectedPackages = $this->config->aiGuidelines ?? [];

        return $skills->filter(
            fn (Skill $skill): bool => in_array($skill->package, $selectedPackages, true)
        );
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function getUserSkills(): Collection
    {
        return $this->discoverPackageSpecificUserSkills()
            ->merge($this->discoverExplicitUserSkills());
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverExplicitUserSkills(): Collection
    {
        $path = base_path('.ai/skills');

        if (! is_dir($path)) {
            return collect();
        }

        return collect(glob($path.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR))
            ->map(fn (string $skillPath): ?Skill => $this->parseSkill($skillPath, 'user', custom: true))
            ->filter()
            ->keyBy(fn (Skill $skill): string => $skill->name);
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverPackageSpecificUserSkills(): Collection
    {
        $userAiPath = base_path('.ai');

        if (! is_dir($userAiPath)) {
            return collect();
        }

        return $this->discoverPackagePaths($userAiPath)
            ->flatMap(fn (array $package): Collection => $this->discoverSkillsFromPath(
                $package['path'],
                $package['name'],
                $package['version']
            )->map(fn (Skill $skill): Skill => $skill->withCustom(true)));
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverSkillsFromPath(string $packagePath, string $packageName, ?string $version): Collection
    {
        $rootSkills = $this->discoverSkillsFromDirectory($packagePath.DIRECTORY_SEPARATOR.'skill', $packageName);

        if ($version === null) {
            return $rootSkills;
        }

        $versionSkills = $this->discoverSkillsFromDirectory($packagePath.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.'skill', $packageName);

        return $rootSkills->merge($versionSkills);
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discoverSkillsFromDirectory(string $skillPath, string $packageName): Collection
    {
        if (! is_dir($skillPath)) {
            return collect();
        }

        return collect(glob($skillPath.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR))
            ->map(fn (string $skillDir): ?Skill => $this->parseSkill($skillDir, $packageName))
            ->filter()
            ->keyBy(fn (Skill $skill): string => $skill->name);
    }

    protected function parseSkill(string $skillPath, string $package = '', bool $custom = false): ?Skill
    {
        $skillFile = $this->findSkillFile($skillPath);

        if ($skillFile === null) {
            return null;
        }

        $content = str_ends_with($skillFile, '.blade.php')
            ? $this->renderBladeFile($skillFile)
            : file_get_contents($skillFile);

        if ($content === false || $content === '') {
            return null;
        }

        $frontmatter = $this->parseSkillFrontmatter($content);

        if (empty($frontmatter['name']) || empty($frontmatter['description'])) {
            return null;
        }

        return new Skill(
            name: $frontmatter['name'],
            package: $package ?: $this->determinePackageFromPath($skillPath),
            path: $skillPath,
            description: $frontmatter['description'],
            custom: $custom,
        );
    }

    protected function findSkillFile(string $skillPath): ?string
    {
        foreach (['SKILL.blade.php', 'SKILL.md'] as $filename) {
            $path = $skillPath.DIRECTORY_SEPARATOR.$filename;

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
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

    protected function determinePackageFromPath(string $skillPath): string
    {
        $parentDir = basename(dirname($skillPath));

        return preg_match('/^\d+(\.\d+)?$/', $parentDir) === 1
            ? basename(dirname($skillPath, 2))
            : $parentDir;
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return new GuidelineAssist($this->roster, $this->config, $this->skills ?? collect());
    }
}
