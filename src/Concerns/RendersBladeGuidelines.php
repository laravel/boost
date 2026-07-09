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

        // Temporarily replace backticks, PHP opening tags, component tags, and Volt directives
        // with placeholders before Blade processing. This prevents Blade from trying to execute
        // PHP code examples, compile component references, and supports inline code.
        $placeholders = [
            '`' => '___SINGLE_BACKTICK___',
            '<?php' => '___OPEN_PHP_TAG___',
            '@volt' => '___VOLT_DIRECTIVE___',
            '@endvolt' => '___ENDVOLT_DIRECTIVE___',
            '</x-' => '___BLADE_COMPONENT_CLOSE___',
            '<x-' => '___BLADE_COMPONENT_OPEN___',
        ];

        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        $rendered = Blade::render($content, [
            'assist' => $this->getGuidelineAssist(),
            ...$data,
        ]);

        $rendered = html_entity_decode((string) $rendered, ENT_QUOTES | ENT_HTML5);

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

    /**
     * Extract `@scoped(['glob/**'])...@endscoped` blocks from raw (unrendered) guideline content.
     *
     * Always collects the blocks so callers can extract path-scoped rules regardless of $remove.
     * When $remove is true, blocks are cut from the returned content (for callers that compose the
     * always-inline blob and want scoped content pulled out); when false, a block's body is spliced
     * back in place with only its `@scoped`/`@endscoped` markers stripped, leaving the file's normal
     * rendering byte-for-byte as if the markers were never there.
     *
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function extractScopedBlocks(string $content, bool $remove): array
    {
        $blocks = [];

        $stripped = preg_replace_callback(
            '/(?<!@)@scoped\(\s*(?P<paths>\[.*?\])\s*\)(?P<body>.*?)@endscoped/s',
            function (array $matches) use (&$blocks, $remove): string {
                $body = trim($matches['body']);

                $blocks[] = [
                    'paths' => $this->parseScopedPaths($matches['paths']),
                    'body' => $body,
                ];

                return $remove ? '' : $body;
            },
            $content
        );

        return ['content' => (string) $stripped, 'blocks' => $blocks];
    }

    /**
     * @return array<int, string>
     */
    protected function parseScopedPaths(string $expression): array
    {
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $expression, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * The raw (unrendered) `@scoped` blocks declared in a guideline file, keyed by nothing in
     * particular — callers render each body themselves via renderBladeString().
     *
     * @return array<int, array{paths: array<int, string>, body: string}>
     */
    protected function scopedBlocksIn(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        return $this->extractScopedBlocks($content, remove: true)['blocks'];
    }

    protected function renderBladeFile(string $bladePath, array $data = [], bool $stripScoped = false): string
    {
        if (! file_exists($bladePath)) {
            return '';
        }

        $content = file_get_contents($bladePath);

        if ($content === false) {
            return '';
        }

        return $this->renderBladeString($content, $bladePath, $data, $stripScoped);
    }

    protected function renderBladeString(string $content, string $path, array $data = [], bool $stripScoped = false): string
    {
        $content = $this->extractScopedBlocks($content, $stripScoped)['content'];
        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $path, $data);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = [];

        return $rendered;
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return app(GuidelineAssist::class);
    }
}
