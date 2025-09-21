<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class GuidelineComposer
{
    protected string $userGuidelineDir = '.ai/guidelines';

    /** @var Collection<string, array> */
    protected Collection $guidelines;

    protected GuidelineConfig $config;

    protected GuidelineAssist $guidelineAssist;

    /**
     * Package priority system to handle conflicts between packages.
     * When a higher-priority package is present, lower-priority packages are excluded from guidelines.
     *
     * @var array<string, string[]>
     */
    protected array $packagePriorities;

    public function __construct(protected Roster $roster, protected Herd $herd)
    {
        $this->packagePriorities = [
            Packages::PEST->value => [Packages::PHPUNIT->value],
        ];
        $this->config = new GuidelineConfig;
        $this->guidelineAssist = new GuidelineAssist($roster);
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
     * @param Collection<string, array{content: string, name: string, path: ?string, custom: bool}> $guidelines
     */
    public static function composeGuidelines(Collection $guidelines): string
    {
        return str_replace("\n\n\n\n", "\n\n", trim($guidelines
            ->filter(fn ($guideline) => ! empty(trim($guideline['content'])))
            ->map(fn ($guideline, $key) => "\n=== {$key} rules ===\n\n".trim($guideline['content']))
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

        return $this->guidelines = $this->find();
    }

    /**
     * Key is the 'guideline key' and value is the rendered blade.
     *
     * @return \Illuminate\Support\Collection<string, array>
     */
    protected function find(): Collection
    {
        $guidelines = collect();
        $guidelines->put('foundation', $this->guideline('foundation'));
        $guidelines->put('boost', $this->guideline('boost/core'));
        $guidelines->put('php', $this->guideline('php/core'));

        // TODO: AI-48: Use composer target version, not PHP version. Production could be 8.1, but local is 8.4
        // $phpMajorMinor = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        // $guidelines->put('php/v'.$phpMajorMinor, $this->guidelinesDir('php/'.$phpMajorMinor));

        if (str_contains(config('app.url'), '.test') && $this->herd->isInstalled()) {
            $guidelines->put('herd', $this->guideline('herd/core'));
        }

        if ($this->config->laravelStyle) {
            $guidelines->put('laravel/style', $this->guideline('laravel/style'));
        }

        if ($this->config->hasAnApi) {
            $guidelines->put('laravel/api', $this->guideline('laravel/api'));
        }

        if ($this->config->caresAboutLocalization) {
            $guidelines->put('laravel/localization', $this->guideline('laravel/localization'));
            // In future, if using NextJS localization/etc.. then have a diff. rule here
        }

        // Add all core and version specific docs for Roster supported packages
        // We don't add guidelines for packages unsupported by Roster right now
        foreach ($this->roster->packages() as $package) {
            // Skip packages that should be excluded due to priority rules
            if ($this->shouldExcludePackage($package->package()->value)) {
                continue;
            }

            $guidelineDir = str_replace('_', '-', strtolower($package->name()));

            $guidelines->put(
                $guidelineDir.'/core',
                $this->guideline($guidelineDir.'/core')
            ); // Always add package core
            $packageGuidelines = $this->guidelinesDir($guidelineDir.'/'.$package->majorVersion());
            foreach ($packageGuidelines as $guideline) {
                $suffix = $guideline['name'] == 'core' ? '' : '/'.$guideline['name'];
                $guidelines->put(
                    $guidelineDir.'/v'.$package->majorVersion().$suffix,
                    $guideline
                );
            }
        }

        if ($this->config->enforceTests) {
            $guidelines->put('tests', $this->guideline('enforce-tests'));
        }

        $userGuidelines = $this->guidelinesDir($this->customGuidelinePath());
        $pathsUsed = $guidelines->pluck('path');

        foreach ($userGuidelines as $guideline) {
            if ($pathsUsed->contains($guideline['path'])) {
                continue; // Don't include this twice if it's an override
            }
            $guidelines->put('.ai/'.$guideline['name'], $guideline);
        }

        return $guidelines
            ->where(fn (array $guideline) => ! empty(trim($guideline['content'])));
    }

    /**
     * Determines if a package should be excluded from guidelines based on priority rules.
     */
    protected function shouldExcludePackage(string $packageName): bool
    {
        foreach ($this->packagePriorities as $priorityPackage => $excludedPackages) {
            if (in_array($packageName, $excludedPackages)) {
                $priorityEnum = Packages::from($priorityPackage);
                if ($this->roster->uses($priorityEnum)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $dirPath
     * @return array<array{content: string, name: string, path: ?string, custom: bool}>
     */
    protected function guidelinesDir(string $dirPath): array
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.blade.php');
        } catch (DirectoryNotFoundException $e) {
            return [];
        }

        return array_map(fn ($file) => $this->guideline($file->getRealPath()), iterator_to_array($finder));
    }

    /**
     * @param string $path
     * @return array{content: string, name: string, path: ?string, custom: bool}
     */
    protected function guideline(string $path): array
    {
        $path = $this->guidelinePath($path);
        if (is_null($path)) {
            return ['content' => '', 'name' => '', 'path' => null, 'custom' => false];
        }

        $content = file_get_contents($path);
        $content = $this->processBoostSnippets($content);

        // Temporarily replace backticks and PHP opening tags with placeholders before Blade processing
        // This prevents Blade from trying to execute PHP code examples and supports inline code
        $placeholders = [
            '`' => '___SINGLE_BACKTICK___',
            '<?php' => '___OPEN_PHP_TAG___',
        ];

        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        $rendered = Blade::render($content, [
            'assist' => $this->guidelineAssist,
        ]);
        $rendered = str_replace(array_values($placeholders), array_keys($placeholders), $rendered);
        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);
        $this->storedSnippets = []; // Clear for next use

        return [
            'content' => trim($rendered),
            'name' => str_replace('.blade.php', '', basename($path)),
            'path' => $path,
            'custom' => str_contains($path, $this->customGuidelinePath()),
        ];
    }

    private array $storedSnippets = [];

    private function processBoostSnippets(string $content): string
    {
        return preg_replace_callback('/(?<!@)@boostsnippet\(\s*(?P<nameQuote>[\'"])(?P<name>[^\1]*?)\1(?:\s*,\s*(?P<langQuote>[\'"])(?P<lang>[^\3]*?)\3)?\s*\)(?P<content>.*?)@endboostsnippet/s', function ($matches) {
            $name = $matches['name'];
            $lang = ! empty($matches['lang']) ? $matches['lang'] : 'html';
            $snippetContent = $matches['content'];

            $placeholder = '___BOOST_SNIPPET_'.count($this->storedSnippets).'___';

            $this->storedSnippets[$placeholder] = '<code-snippet name="'.$name.'" lang="'.$lang.'">'."\n".$snippetContent."\n".'</code-snippet>'."\n\n";

            return $placeholder;
        }, $content);
    }

    protected function prependPackageGuidelinePath(string $path): string
    {
        $path = preg_replace('/\.blade\.php$/', '', $path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$path.'.blade.php');

        return $path;
    }

    protected function prependUserGuidelinePath(string $path): string
    {
        $path = preg_replace('/\.blade\.php$/', '', $path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, $this->customGuidelinePath($path.'.blade.php'));

        return $path;
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
        $relativePath = ltrim(str_replace([$basePath, '.ai'.DIRECTORY_SEPARATOR, '.ai/'], '', $path), '/\\');
        $customPath = $this->prependUserGuidelinePath($relativePath);

        return file_exists($customPath) ? $customPath : $path;
    }
}
