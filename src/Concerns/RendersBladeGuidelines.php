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
            '@can' => '___CAN_DIRECTIVE___',
            '@include' => '___INCLUDE_DIRECTIVE___',
            '@props' => '___PROPS_DIRECTIVE___',
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

    protected function markScopedBlocks(string $content): string
    {
        $fences = [];

        $marked = preg_replace_callback('/(?<fence>`{3,}|~{3,}).*?\k<fence>/s', function (array $matches) use (&$fences): string {
            $placeholder = '___SCOPED_FENCE_'.count($fences).'___';
            $fences[$placeholder] = $matches[0];

            return $placeholder;
        }, $content);

        if ($marked === null) {
            return $content;
        }

        $marked = preg_replace_callback(
            '/(?<!@)@scoped\(\s*(?P<paths>\[(?:[\s,]|\'[^\']*\'|"[^"]*")*\])\s*\)/s',
            fn (array $matches): string => '___SCOPED_START_'.base64_encode((string) json_encode($this->parseScopedPaths($matches['paths']))).'___',
            $marked
        );

        if ($marked === null) {
            return $content;
        }

        $marked = preg_replace('/(?<!@)@endscoped/', '___SCOPED_END___', $marked);

        if ($marked === null) {
            return $content;
        }

        return str_replace(array_keys($fences), array_values($fences), $marked);
    }

    /**
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function extractScopedBlocks(string $content, bool $remove): array
    {
        $blocks = [];

        $matched = preg_match_all(
            '/___SCOPED_START_(?P<paths>[A-Za-z0-9+\/=]*)___|___SCOPED_END___/',
            $content,
            $tokens,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        if ($matched === false || $matched === 0) {
            return ['content' => $this->stripScopedSentinels($content), 'blocks' => []];
        }

        $result = '';
        $cursor = 0;
        $depth = 0;
        $bodyStart = 0;
        $blockPaths = [];
        $nested = false;

        foreach ($tokens as $token) {
            $text = $token[0][0];
            $offset = $token[0][1];

            if (str_starts_with($text, '___SCOPED_START_')) {
                if ($depth === 0) {
                    $result .= substr($content, $cursor, $offset - $cursor);
                    $cursor = $offset;
                    $bodyStart = $offset + strlen($text);
                    $blockPaths = $this->decodeScopedPaths($token['paths'][0]);
                    $nested = false;
                } else {
                    $nested = true;
                }

                $depth++;

                continue;
            }

            if ($depth === 0) {
                continue;
            }

            $depth--;

            if ($depth !== 0) {
                continue;
            }

            $body = $this->stripScopedSentinels(substr($content, $bodyStart, $offset - $bodyStart));

            if ($nested || $blockPaths === []) {
                $result .= $body;
            } else {
                $blocks[] = ['paths' => $blockPaths, 'body' => trim((string) $body)];
                $result .= $remove ? '' : $body;
            }

            $cursor = $offset + strlen($text);
        }

        $result .= substr($content, $cursor);

        return ['content' => $this->stripScopedSentinels($result), 'blocks' => $blocks];
    }

    protected function stripScopedSentinels(string $content): string
    {
        return preg_replace('/___SCOPED_(?:START_[A-Za-z0-9+\/=]*|END)___/', '', $content) ?? $content;
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

    /**
     * @return array{content: string, blocks: array<int, array{paths: array<int, string>, body: string}>}
     */
    protected function renderBladeStringWithScopedBlocks(string $content, string $path, array $data = [], bool $stripScoped = false): array
    {
        $content = $this->processBoostSnippets($content);
        $content = $this->markScopedBlocks($content);

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
