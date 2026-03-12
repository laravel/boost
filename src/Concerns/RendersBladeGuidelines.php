<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Support\Facades\Blade;
use Laravel\Boost\Install\GuidelineAssist;

trait RendersBladeGuidelines
{
    private array $storedSnippets = [];

    protected function renderContent(string $content, string $path, array $data = []): string
    {
        $isBladeTemplate = str_ends_with($path, '.blade.php');

        if (! $isBladeTemplate) {
            return $content;
        }

        // Temporarily replace backticks and PHP opening tags with placeholders before Blade processing
        // This prevents Blade from trying to execute PHP code examples and supports inline code
        $placeholders = [
            '`' => '___SINGLE_BACKTICK___',
            '<?php' => '___OPEN_PHP_TAG___',
            '@volt' => '___VOLT_DIRECTIVE___',
            '@endvolt' => '___ENDVOLT_DIRECTIVE___',
        ];

        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        $rendered = Blade::render($content, [
            'assist' => $this->getGuidelineAssist(),
            ...$data,
        ]);

        $rendered = html_entity_decode($rendered, ENT_QUOTES | ENT_HTML5);

        return str_replace(array_values($placeholders), array_keys($placeholders), $rendered);
    }

    protected function processBoostSnippets(string $content): string
    {
        return preg_replace_callback('/(?<!@)@boostsnippet\(\s*(?P<nameQuote>[\'"])(?P<name>[^\1]*?)\1(?:\s*,\s*(?P<langQuote>[\'"])(?P<lang>[^\3]*?)\3)?\s*\)(?P<content>.*?)@endboostsnippet/s', function (array $matches): string {
            $name = $matches['name'];
            $lang = empty($matches['lang']) ? 'html' : $matches['lang'];
            $snippetContent = trim($matches['content']);

            $placeholder = '___BOOST_SNIPPET_'.count($this->storedSnippets).'___';

            $this->storedSnippets[$placeholder] = '<!-- '.$name.' -->'."\n".'```'.$lang."\n".$snippetContent."\n".'```'."\n\n";

            return $placeholder;
        }, $content);
    }

    protected function renderBladeFile(string $bladePath, array $data = []): string
    {
        if (! file_exists($bladePath)) {
            return '';
        }

        $content = file_get_contents($bladePath);
        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $bladePath, $data);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = [];

        return $rendered;
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return app(GuidelineAssist::class);
    }
}
