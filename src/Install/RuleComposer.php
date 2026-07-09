<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\BuildsGuidelineAssist;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\Concerns\DiscoverPackagePaths;
use Laravel\Boost\Rules\RuleFrontmatter;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RuleComposer
{
    use BuildsGuidelineAssist;
    use DiscoverPackagePaths;
    use RendersBladeGuidelines;

    /** @var Collection<string, array{name: string, paths: array<int, string>, content: string, third_party: bool}>|null */
    protected ?Collection $rules = null;

    public function __construct(protected Roster $roster, protected GuidelineConfig $config = new GuidelineConfig) {}

    protected function getRoster(): Roster
    {
        return $this->roster;
    }

    public function config(GuidelineConfig $config): self
    {
        $this->config = $config;
        $this->rules = null;

        return $this;
    }

    /**
     * All discovered path-scoped rules, keyed by a stable identifier.
     *
     * @return Collection<string, array{name: string, paths: array<int, string>, content: string, third_party: bool}>
     */
    public function rules(): Collection
    {
        if ($this->rules instanceof Collection) {
            return $this->rules;
        }

        $excluded = config('boost.guidelines.exclude', []);

        return $this->rules = collect()
            ->merge($this->getPackageRules())
            ->merge($this->getThirdPartyRules())
            ->reject(fn (array $rule, string $key): bool => in_array($key, $excluded, true))
            ->filter(fn (array $rule): bool => filled($rule['content']) && $rule['paths'] !== []);
    }

    /**
     * Group scoped rules by their normalized paths set, merging rules that apply to the
     * exact same globs into a single managed rule file payload.
     *
     * @return Collection<string, array{paths: array<int, string>, title: string, content: string}>
     */
    public function composeManaged(): Collection
    {
        $slugs = [];

        return $this->rules()
            ->groupBy(fn (array $rule): string => $this->pathsKey($rule['paths']))
            ->mapWithKeys(function (Collection $group) use (&$slugs): array {
                $paths = collect($group->first()['paths'])
                    ->map(fn (string $path): string => trim($path))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $content = MarkdownFormatter::format(
                    $group->map(fn (array $rule): string => trim($rule['content']))->filter()->join("\n\n")
                );

                $slug = $this->uniqueSlug($paths, $slugs);
                $slugs[] = $slug;

                return [$slug => [
                    'paths' => $paths,
                    'title' => $this->titleFor($content, $slug),
                    'content' => $content,
                ]];
            });
    }

    /**
     * Flag-off fallback: expose scoped rules as guideline-shaped entries so their content can be
     * merged into the inline composed blob instead of extracted into `.ai/rules`.
     *
     * @return Collection<string, array{content: string, name: string, description: string, path: null, custom: bool, third_party: bool, tokens: float}>
     */
    public function composeInline(): Collection
    {
        return $this->rules()->map(fn (array $rule): array => [
            'content' => $rule['content'],
            'name' => $rule['name'],
            'description' => Str::of($rule['content'])
                ->after('# ')
                ->before("\n")
                ->trim()
                ->limit(50)
                ->whenEmpty(fn () => Str::of('No description provided'))
                ->value(),
            'path' => null,
            'custom' => false,
            'third_party' => $rule['third_party'],
            'tokens' => round(str_word_count($rule['content']) * 1.3),
        ]);
    }

    protected function getPackageRules(): Collection
    {
        return $this->roster->packages()
            ->reject(fn (Package $package): bool => $this->shouldExcludePackage($package))
            ->flatMap(function (Package $package): Collection {
                $name = $this->normalizePackageName($package->name());
                $vendorPath = $this->resolveFirstPartyBoostPath($package, 'rules');

                // Merge by filename so a vendor override doesn't hide other bundled files.
                $bundled = collect($this->rulesDir($name.'/rules'))->keyBy(fn (array $rule): string => $rule['name']);
                $vendor = $vendorPath !== null
                    ? collect($this->rulesDir($vendorPath))->keyBy(fn (array $rule): string => $rule['name'])
                    : collect();

                $rules = collect();

                foreach ($bundled->merge($vendor) as $rule) {
                    $rules->put($name.'/rules/'.$rule['name'], $rule);
                }

                $majorVersion = $package->majorVersion();

                if (filled($majorVersion)) {
                    foreach ($this->rulesDir($name.'/'.$majorVersion.'/rules') as $rule) {
                        $rules->put($name.'/v'.$majorVersion.'/rules/'.$rule['name'], $rule);
                    }
                }

                return $rules;
            });
    }

    /**
     * @return Collection<string, array{name: string, paths: array<int, string>, content: string, third_party: bool}>
     */
    protected function getThirdPartyRules(): Collection
    {
        $rules = collect();
        $selected = $this->config->aiGuidelines ?? null;

        foreach (Composer::packagesDirectoriesWithBoostRules() as $package => $path) {
            if (Composer::isFirstPartyPackage($package)) {
                continue;
            }

            if ($selected !== null && ! in_array($package, $selected, true)) {
                continue;
            }

            foreach ($this->rulesDir($path, thirdParty: true) as $rule) {
                $rules->put($package.'/rules/'.$rule['name'], $rule);
            }
        }

        return $rules;
    }

    /**
     * @return array<int, array{name: string, paths: array<int, string>, content: string, third_party: bool}>
     */
    protected function rulesDir(string $dirPath, bool $thirdParty = false): array
    {
        if (! is_dir($dirPath)) {
            $dirPath = $this->getBoostAiPath().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.blade.php')
                ->name('*.md')
                ->sortByName();
        } catch (DirectoryNotFoundException) {
            return [];
        }

        return collect($finder)
            ->map(fn (SplFileInfo $file): ?array => $this->parseRuleFile($file->getRealPath(), $thirdParty))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, paths: array<int, string>, content: string, third_party: bool}|null
     */
    protected function parseRuleFile(string $path, bool $thirdParty = false): ?array
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        ['paths' => $paths, 'body' => $body] = RuleFrontmatter::parse($raw);

        if ($paths === []) {
            return null;
        }

        $content = str_ends_with($path, '.blade.php')
            ? $this->renderBladeString($body, $path)
            : $body;

        return [
            'name' => str_replace(['.blade.php', '.md'], '', basename($path)),
            'paths' => $paths,
            'content' => trim($content),
            'third_party' => $thirdParty,
        ];
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function pathsKey(array $paths): string
    {
        return collect($paths)->map(fn (string $path): string => trim($path))->unique()->sort()->values()->join('|');
    }

    protected function titleFor(string $content, string $fallbackSlug): string
    {
        $heading = Str::of($content)->after('# ')->before("\n")->trim()->value();

        return filled($heading) ? $heading : Str::headline($fallbackSlug);
    }

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $taken
     */
    protected function uniqueSlug(array $paths, array $taken): string
    {
        $lastSegments = collect($paths)
            ->map(fn (string $glob): array => Str::of($glob)
                ->explode('/')
                ->filter(fn (string $segment): bool => filled($segment) && ! str_contains($segment, '*') && ! str_contains($segment, '.'))
                ->values()
                ->all())
            ->map(fn (array $segments): string => (string) end($segments))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $base = $lastSegments->isEmpty() ? 'rules' : Str::slug(Str::snake($lastSegments->join(' ')));

        if ($base === '') {
            $base = 'rules';
        }

        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $suffix = 2;

        while (in_array($base.'-'.$suffix, $taken, true)) {
            $suffix++;
        }

        return $base.'-'.$suffix;
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return $this->buildGuidelineAssist();
    }
}
