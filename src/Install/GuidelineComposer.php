<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class GuidelineComposer
{
    use RendersBladeGuidelines;

    protected string $userGuidelineDir = '.ai/guidelines';

    /** @var Collection<string, array> */
    protected Collection $guidelines;

    /** @var Collection<string, Skill> */
    protected Collection $skills;

    protected GuidelineConfig $config;

    /**
     * Package priority system to handle conflicts between packages.
     * When a higher-priority package is present, lower-priority packages are excluded from guidelines.
     *
     * @var array<string, string[]>
     */
    protected array $packagePriorities;

    /**
     * Only include guidelines for these package names if they're a direct requirement.
     * This fixes every Boost user getting the MCP guidelines due to indirect import.
     *
     * @var array<int, Packages>
     * */
    protected array $mustBeDirect = [
        Packages::MCP,
    ];

    /**
     * Packages that should be excluded from automatic guideline inclusion.
     * These packages require explicit configuration to be included.
     *
     * @var array<int, Packages>
     */
    protected array $optInPackages = [
        Packages::SAIL,
    ];

    public function __construct(protected Roster $roster, protected Herd $herd)
    {
        $this->packagePriorities = [
            Packages::PEST->value => [Packages::PHPUNIT->value],
            Packages::FLUXUI_PRO->value => [Packages::FLUXUI_FREE->value],
        ];
        $this->config = new GuidelineConfig;
    }

    public function config(GuidelineConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Auto discovers the guideline files and composes them into one string.
     */
    public function compose(): string
    {
        return self::composeGuidelines($this->guidelines());
    }

    public function customGuidelinePath(string $path = ''): string
    {
        return base_path($this->userGuidelineDir.'/'.ltrim($path, '/'));
    }

    /**
     * Static method to compose guidelines from a collection.
     * Can be used without Laravel dependencies.
     *
     * @param  Collection<string, array{content: string, name: string, path: ?string, custom: bool}>  $guidelines
     */
    public static function composeGuidelines(Collection $guidelines): string
    {
        return str_replace("\n\n\n\n", "\n\n", trim($guidelines
            ->filter(fn ($guideline): bool => ! empty(trim($guideline['content'])))
            ->map(fn ($guideline, $key): string => "\n=== {$key} rules ===\n\n".trim($guideline['content']))
            ->join("\n\n"))
        );
    }

    /**
     * @return string[]
     */
    public function used(): array
    {
        return $this->guidelines()->keys()->toArray();
    }

    /**
     * @return Collection<string, array>
     */
    public function guidelines(): Collection
    {
        if (! empty($this->guidelines)) {
            return $this->guidelines;
        }

        $base = collect()
            ->merge($this->getCoreGuidelines())
            ->merge($this->getConditionalGuidelines())
            ->merge($this->getPackageGuidelines())
            ->merge($this->getThirdPartyGuidelines());

        $basePaths = $base->pluck('path')->filter()->values();

        $customGuidelines = $this->getUserGuidelines()
            ->reject(fn ($guideline): bool => $basePaths->contains($guideline['path']));

        return $this->guidelines = $customGuidelines
            ->merge($base)
            ->filter(fn ($guideline): bool => filled($guideline['content']));
    }

    /**
     * @return Collection<string, array>
     */
    protected function getUserGuidelines(): Collection
    {
        return collect($this->guidelinesDir($this->customGuidelinePath()))
            ->mapWithKeys(fn ($guideline): array => ['.ai/'.$guideline['name'] => $guideline]);
    }

    /**
     * @return Collection<string, array>
     */
    protected function getCoreGuidelines(): Collection
    {
        return collect([
            'foundation' => $this->guideline('foundation'),
            'boost' => $this->guideline('boost/core'),
            'php' => $this->guideline('php/core'),
        ]);
    }

    /**
     * @return Collection<string, array>
     */
    protected function getConditionalGuidelines(): Collection
    {
        return collect([
            'herd' => [
                'condition' => str_contains((string) config('app.url'), '.test') && $this->herd->isInstalled() && ! $this->config->usesSail,
                'path' => 'herd/core',
            ],
            'sail' => [
                'condition' => $this->config->usesSail,
                'path' => 'sail/core',
            ],
            'laravel/style' => [
                'condition' => $this->config->laravelStyle,
                'path' => 'laravel/style',
            ],
            'laravel/api' => [
                'condition' => $this->config->hasAnApi,
                'path' => 'laravel/api',
            ],
            'laravel/localization' => [
                'condition' => $this->config->caresAboutLocalization,
                'path' => 'laravel/localization',
            ],
            'tests' => [
                'condition' => $this->config->enforceTests,
                'path' => 'enforce-tests',
            ],
        ])
            ->filter(fn ($config): bool => $config['condition'])
            ->mapWithKeys(fn ($config, $key): array => [$key => $this->guideline($config['path'])]);
    }

    protected function getPackageGuidelines(): PackageCollection
    {
        return $this->roster->packages()
            ->reject(fn (Package $package): bool => $this->shouldExcludePackage($package))
            ->flatMap(function ($package): Collection {
                $guidelineDir = str_replace('_', '-', strtolower($package->name()));
                $guidelines = collect([$guidelineDir.'/core' => $this->guideline($guidelineDir.'/core')]);
                $packageGuidelines = $this->guidelinesDir($guidelineDir.'/'.$package->majorVersion());

                foreach ($packageGuidelines as $guideline) {
                    $suffix = $guideline['name'] === 'core' ? '' : '/'.$guideline['name'];

                    $guidelines->put(
                        $guidelineDir.'/v'.$package->majorVersion().$suffix,
                        $guideline
                    );
                }

                return $guidelines;
            });
    }

    /**
     * @return Collection<string, array>
     */
    protected function getThirdPartyGuidelines(): Collection
    {
        $guidelines = collect();

        foreach (Composer::packagesDirectoriesWithBoostGuidelines() as $package => $path) {
            foreach ($this->guidelinesDir($path, true) as $guideline) {
                $guidelines->put($package, $guideline);
            }
        }

        if (! isset($this->config->aiGuidelines)) {
            return $guidelines;
        }

        return $guidelines->filter(
            fn (mixed $guideline, string $name): bool => in_array($name, $this->config->aiGuidelines, true),
        );
    }

    /**
     * Determines if a package should be excluded from guidelines based on priority rules.
     */
    protected function shouldExcludePackage(Package $package): bool
    {
        if (in_array($package->package(), $this->optInPackages, true)) {
            return true;
        }

        foreach ($this->packagePriorities as $priorityPackage => $excludedPackages) {
            $packageIsInExclusionList = in_array($package->package()->value, $excludedPackages, true);

            if ($packageIsInExclusionList && $this->roster->uses(Packages::from($priorityPackage))) {
                return true;
            }
        }

        return $package->indirect() && in_array($package->package(), $this->mustBeDirect, true);
    }

    /**
     * @return array<array{content: string, name: string, description: string, path: ?string, custom: bool, third_party: bool}>
     */
    protected function guidelinesDir(string $dirPath, bool $thirdParty = false): array
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->exclude('skill')
                ->name('*.blade.php')
                ->name('*.md');
        } catch (DirectoryNotFoundException) {
            return [];
        }

        return collect($finder)
            ->map(fn (SplFileInfo $file): array => $this->guideline($file->getRealPath(), $thirdParty))
            ->all();
    }

    /**
     * @return array{content: string, name: string, description: string, path: ?string, custom: bool, third_party: bool}
     */
    protected function guideline(string $path, bool $thirdParty = false): array
    {
        $path = $this->guidelinePath($path);
        if (is_null($path)) {
            return [
                'content' => '',
                'description' => '',
                'name' => '',
                'path' => null,
                'custom' => false,
                'third_party' => $thirdParty,
            ];
        }

        $content = file_get_contents($path);
        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $path);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = []; // Clear for next use

        $description = Str::of($rendered)
            ->after('# ')
            ->before("\n")
            ->trim()
            ->limit(50)
            ->whenEmpty(fn () => Str::of('No description provided'))
            ->value();

        return [
            'content' => trim($rendered),
            'name' => str_replace(['.blade.php', '.md'], '', basename($path)),
            'description' => $description,
            'path' => $path,
            'custom' => str_contains($path, $this->customGuidelinePath()),
            'third_party' => $thirdParty,
            'tokens' => round(str_word_count($rendered) * 1.3),
        ];
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return new GuidelineAssist($this->roster, $this->config, $this->skills());
    }

    protected function prependPackageGuidelinePath(string $path): string
    {
        return $this->prependGuidelinePath($path, __DIR__.'/../../.ai/');
    }

    protected function prependUserGuidelinePath(string $path): string
    {
        return $this->prependGuidelinePath($path, $this->customGuidelinePath());
    }

    private function prependGuidelinePath(string $path, string $basePath): string
    {
        if (! str_ends_with($path, '.md') && ! str_ends_with($path, '.blade.php')) {
            $path .= '.blade.php';
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $basePath.$path);
    }

    protected function guidelinePath(string $path): ?string
    {
        // Relative path, prepend our package path to it
        if (! file_exists($path)) {
            $path = $this->prependPackageGuidelinePath($path);
            if (! file_exists($path)) {
                return null;
            }
        }

        $path = realpath($path);

        // If this is a custom guideline, return it unchanged
        if (str_contains($path, $this->customGuidelinePath())) {
            return $path;
        }

        // The path is not a custom guideline, check if the user has an override for this
        $basePath = realpath(__DIR__.'/../../');
        $relativePath = Str::of($path)
            ->replace([$basePath, '.ai'.DIRECTORY_SEPARATOR, '.ai/'], '')
            ->ltrim('/\\')
            ->toString();

        $customPath = $this->prependUserGuidelinePath($relativePath);

        return file_exists($customPath) ? $customPath : $path;
    }

    /**
     * @return Collection<string, Skill>
     */
    public function skills(): Collection
    {
        if (! empty($this->skills)) {
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

        return $this->roster->packages()
            ->map(function ($package) use ($aiPath): array {
                $packageDirName = str_replace('_', '-', strtolower($package->name()));

                return [
                    'path' => $aiPath.$packageDirName,
                    'name' => $packageDirName,
                    'version' => $package->majorVersion(),
                ];
            })
            ->filter(fn (array $pkg): bool => is_dir($pkg['path']))
            ->flatMap(fn (array $pkg): Collection => $this->discoverSkillsFromPath(
                $pkg['path'],
                $pkg['name'],
                $pkg['version']
            ));
    }

    /**
     * Discover skills from a package path, handling version-specific and root-level skills.
     *
     * Version-specific skills take precedence over root-level skills with the same name.
     *
     * @return Collection<string, Skill>
     */
    protected function discoverSkillsFromPath(string $packagePath, string $packageName, ?string $installedVersion): Collection
    {
        $versionSpecificSkills = $this->discoverSkillsFromDirectory(
            $packagePath.'/'.$installedVersion.'/skill',
            $packageName
        );

        $rootSkills = $this->discoverSkillsFromDirectory(
            $packagePath.'/skill',
            $packageName
        );

        // Version-specific skills override root skills with the same name
        return $rootSkills->merge($versionSpecificSkills);
    }

    /**
     * Discover skills from a specific directory.
     *
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

    /**
     * Determines if a directory name represents a version (e.g., 3, 11, 8.2).
     */
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

    /**
     * Get the major version of an installed package by its composer name (vendor/package).
     */
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
        $userSkillsPath = base_path('.ai/skills');

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

    /**
     * Find the skill file in a directory, preferring SKILL.blade.php over SKILL.md.
     */
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

    /**
     * Determine package name from the skill path structure.
     *
     * Handles paths like .ai/{package}/skill/ or .ai/{package}/{version}/skill/
     */
    protected function determinePackageFromPath(string $skillPath): string
    {
        $parentDir = basename(dirname($skillPath));

        return $this->isVersionDirectory($parentDir)
            ? basename(dirname($skillPath, 2))
            : $parentDir;
    }

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
}
