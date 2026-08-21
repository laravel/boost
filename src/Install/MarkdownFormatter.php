<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class MarkdownFormatter
{
    /**
     * Apply consistent formatting to markdown content.
     */
    public static function format(string $content): string
    {
        // Normalize line endings (CRLF → LF, CR → LF)
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $fences = [];

        // A "# " line inside a fence is code, not a heading, so hide fences while spacing headings.
        $masked = preg_replace_callback('/(?<fence>`{3,}|~{3,}).*?\k<fence>/s', function (array $matches) use (&$fences): string {
            $placeholder = '___MARKDOWN_FENCE_'.count($fences).'___';
            $fences[$placeholder] = $matches[0];

            return $placeholder;
        }, $content);

        if ($masked === null) {
            return $content;
        }

        // Ensure blank line before and after markdown headings
        $masked = preg_replace('/(?<!\n)\n(#{1,4} )/m', "\n\n$1", $masked);
        $masked = preg_replace('/^(#{1,4} .+)\n(?!\n)/m', "$1\n\n", (string) $masked);

        // Collapse multiple consecutive empty lines into a single empty line
        $masked = preg_replace('/\n{3,}/', "\n\n", (string) $masked);

        return str_replace(array_keys($fences), array_values($fences), (string) $masked);
    }
}
