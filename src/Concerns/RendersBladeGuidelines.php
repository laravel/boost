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
     * Swap `@scoped` markers for plain-text sentinels so bodies render in their Blade context.
     */
    protected function markScopedBlocks(string $content): string
    {
        $content = (string) preg_replace_callback(
            '/(?<!@)@scoped\(\s*(?P<paths>\[(?:[\s,]|\'[^\']*\'|"[^"]*")*\])\s*\)/s',
            fn (array $matches): string => '___SCOPED_START_'.base64_encode((string) json_encode($this->parseScopedPaths($matches['paths']))).'___',
            $content
        );

        return (string) preg_replace('/(?<!@)@endscoped/', '___SCOPED_END___', $content);
    }

    /**
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function extractScopedBlocks(string $content, bool $remove): array
    {
        $blocks = [];

        $stripped = (string) preg_replace_callback(
            '/___SCOPED_START_(?P<paths>[A-Za-z0-9+\/=]*)___(?P<body>.*?)___SCOPED_END___/s',
            function (array $matches) use (&$blocks, $remove): string {
                $paths = $this->decodeScopedPaths($matches['paths']);
                $body = trim($this->stripScopedSentinels($matches['body']));

                $blocks[] = [
                    'paths' => $paths,
                    'body' => $body,
                ];

                // A pathless block can never become a rule file, so it stays inline.
                return $remove && $paths !== [] ? '' : $body;
            },
            $content
        );

        return ['content' => $this->stripScopedSentinels($stripped), 'blocks' => $blocks];
    }

    protected function stripScopedSentinels(string $content): string
    {
        return (string) preg_replace('/___SCOPED_(?:START_[A-Za-z0-9+\/=]*|END)___/', '', $content);
    }

    /**
     * @return array<int, string>
     */
    protected function decodeScopedPaths(string $encoded): array
    {
        $decoded = json_decode(base64_decode($encoded, true) ?: '', true);

        return array_values(array_filter(is_array($decoded) ? $decoded : [], is_string(...)));
    }

    /**
     * @return array<int, string>
     */
    protected function parseScopedPaths(string $expression): array
    {
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $expression, $matches);

        return array_values(array_unique($matches[1]));
    }

    protected function renderBladeFile(string $bladePath, array $data = [], bool $stripScoped = false): string
    {
        return $this->renderBladeFileWithScopedBlocks($bladePath, $data, $stripScoped)['content'];
    }

    /**
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function renderBladeFileWithScopedBlocks(string $bladePath, array $data = [], bool $stripScoped = false): array
    {
        if (! file_exists($bladePath)) {
            return ['content' => '', 'blocks' => []];
        }

        $content = file_get_contents($bladePath);

        if ($content === false) {
            return ['content' => '', 'blocks' => []];
        }

        return $this->renderBladeStringWithScopedBlocks($content, $bladePath, $data, $stripScoped);
    }

    protected function renderBladeString(string $content, string $path, array $data = [], bool $stripScoped = false): string
    {
        return $this->renderBladeStringWithScopedBlocks($content, $path, $data, $stripScoped)['content'];
    }

    /**
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function renderBladeStringWithScopedBlocks(string $content, string $path, array $data = [], bool $stripScoped = false): array
    {
        $content = $this->markScopedBlocks($content);
        $content = $this->processBoostSnippets($content);

        $rendered = $this->renderContent($content, $path, $data);

        $rendered = str_replace(array_keys($this->storedSnippets), array_values($this->storedSnippets), $rendered);

        $this->storedSnippets = [];

        return $this->extractScopedBlocks($rendered, $stripScoped);
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return app(GuidelineAssist::class);
    }
}
