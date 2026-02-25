<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\Concerns\DiscoverPackagePaths;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class GuidelineComposer
{
    use DiscoverPackagePaths;
    use RendersBladeGuidelines;

    protected string $userGuidelineDir = '.ai/guidelines';

    /** @var Collection<string, array>|null */
    protected ?Collection $guidelines = null;

    protected GuidelineConfig $config;

    protected ?SkillComposer $skillComposer = null;

    public function __construct(protected Roster $roster, protected Herd $herd)
    {
        $this->config = new GuidelineConfig;
    }

    protected function getRoster(): Roster
    {
        return $this->roster;
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
        $composed = trim($guidelines
            ->filter(fn ($guideline): bool => ! empty(trim($guideline['content'])))
            ->map(fn ($guideline, $key): string => "\n=== {$key} rules ===\n\n".trim($guideline['content']))
            ->join("\n\n")
        );

        return MarkdownFormatter::format($composed);
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
        if ($this->guidelines instanceof Collection) {
            return $this->guidelines;
        }

        $excluded = config('boost.guidelines.exclude', []);

        $base = collect()
            ->merge($this->getCoreGuidelines())
            ->merge($this->getConditionalGuidelines())
            ->merge($this->getPackageGuidelines())
            ->merge($this->getThirdPartyGuidelines())
            ->reject(fn (array $guideline, string $key): bool => in_array($key, $excluded, true));

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

    protected function getPackageGuidelines(): Collection
    {
        return $this->roster->packages()
            ->reject(fn (Package $package): bool => $this->shouldExcludePackage($package))
            ->flatMap(function (Package $package): Collection {
                $guidelineDir = $this->normalizePackageName($package->name());

                $vendorPath = $this->resolveFirstPartyBoostPath($package, 'guidelines');

                $vendorCorePath = $vendorPath !== null
                    ? implode(DIRECTORY_SEPARATOR, [$vendorPath, 'core'])
                    : null;

                $guidelines = collect([
                    $guidelineDir.'/core' => $this->resolveGuideline($vendorCorePath, $guidelineDir.'/core'),
                ]);

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
     * @return array{content: string, name: string, description: string, path: ?string, custom: bool, third_party: bool}
     */
    private function resolveGuideline(?string $vendorPath, string $guidelineKey): array
    {
        if ($vendorPath !== null) {
            foreach (['.blade.php', '.md'] as $ext) {
                if (file_exists($vendorPath.$ext)) {
                    return $this->guideline($vendorPath.$ext, false, $guidelineKey);
                }
            }
        }

        return $this->guideline($guidelineKey);
    }

    /**
     * @return Collection<string, array>
     */
    protected function getThirdPartyGuidelines(): Collection
    {
        $guidelines = collect();

        foreach (Composer::packagesDirectoriesWithBoostGuidelines() as $package => $path) {
            if (Composer::isFirstPartyPackage($package)) {
                continue;
            }

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
     * @return array<array{content: string, name: string, description: string, path: ?string, custom: bool, third_party: bool}>
     */
    protected function guidelinesDir(string $dirPath, bool $thirdParty = false): array
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, $this->getBoostAiPath().'/'.$dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->exclude('skill')
                ->name('*.blade.php')
                ->name('*.md')
                ->sortByName();
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
    protected function guideline(string $path, bool $thirdParty = false, ?string $overrideKey = null): array
    {
        $path = $this->guidelinePath($path, $overrideKey);

        if ($path === null) {
            return [
                'content' => '',
                'description' => '',
                'name' => '',
                'path' => null,
                'custom' => false,
                'third_party' => $thirdParty,
            ];
        }

        $rendered = $this->renderBladeFile($path);

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
        $skillsComposer = $this->skillComposer ??= new SkillComposer($this->roster, $this->config);

        return new GuidelineAssist($this->roster, $this->config, $skillsComposer->skills());
    }

    protected function prependPackageGuidelinePath(string $path): string
    {
        return $this->prependGuidelinePath($path, $this->getBoostAiPath().'/');
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

    protected function guidelinePath(string $path, ?string $overrideKey = null): ?string
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

        if ($overrideKey !== null) {
            foreach (['.blade.php', '.md'] as $ext) {
                $customPath = $this->prependUserGuidelinePath($overrideKey.$ext);

                if (file_exists($customPath)) {
                    return $customPath;
                }
            }

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
}
