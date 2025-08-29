<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class GuidelineComposer
{
    protected string $userGuidelineDir = '.ai/guidelines';

    /** @var Collection<string, string> */
    protected Collection $guidelines;

    protected GuidelineConfig $config;

    protected GuidelineAssist $guidelineAssist;

    public function __construct(protected Roster $roster, protected Herd $herd)
    {
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

    /**
     * Static method to compose guidelines from a collection.
     * Can be used without Laravel dependencies.
     *
     * @param Collection<string, string> $guidelines
     */
    public static function composeGuidelines(Collection $guidelines): string
    {
        return str_replace("\n\n\n\n", "\n\n", trim($guidelines
            ->filter(fn ($content) => ! empty(trim($content)))
            ->map(fn ($content, $key) => "\n=== {$key} rules ===\n\n".trim($content))
            ->join("\n\n")));
    }

    /**
     * @return string[]
     */
    public function used(): array
    {
        return $this->guidelines()->keys()->toArray();
    }

    /**
     * @return Collection<string, string>
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
     * @return \Illuminate\Support\Collection<string, string>
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
            $guidelineDir = str_replace('_', '-', strtolower($package->name()));

            $guidelines->put(
                $guidelineDir.'/core',
                $this->guideline($guidelineDir.'/core')
            ); // Always add package core

            $guidelines->put(
                $guidelineDir.'/v'.$package->majorVersion(),
                $this->guidelinesDir($guidelineDir.'/'.$package->majorVersion())
            );
        }

        if ($this->config->enforceTests) {
            $guidelines->put('tests', $this->guideline('enforce-tests'));
        }

        $userGuidelines = $this->guidelineFilesInDir(base_path($this->userGuidelineDir));

        foreach ($userGuidelines as $guideline) {
            $guidelineKey = '.ai/'.$guideline->getBasename('.blade.php');
            $guidelines->put($guidelineKey, $this->guideline($guideline->getPathname()));
        }

        return $guidelines
            ->whereNotNull()
            ->where(fn (string $guideline) => ! empty(trim($guideline)));
    }

    /**
     * @return Collection<string, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function guidelineFilesInDir(string $dirPath): Collection
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
        }

        try {
            return collect(iterator_to_array(Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.blade.php')));
        } catch (DirectoryNotFoundException $e) {
            return collect();
        }
    }

    protected function guidelinesDir(string $dirPath): ?string
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
            return null;
        }

        $guidelines = '';
        foreach ($finder as $file) {
            $guidelines .= $this->guideline($file->getRealPath()) ?? '';
            $guidelines .= PHP_EOL;
        }

        return $guidelines;
    }

    protected function guideline(string $path): ?string
    {
        if (! file_exists($path)) {
            $path = preg_replace('/\.blade\.php$/', '', $path);
            $path = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$path.'.blade.php');
        }

        if (! file_exists($path)) {
            return null;
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

        return trim($rendered);
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
}
