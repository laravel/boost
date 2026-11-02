<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Support\Fences;

class MarkdownFormatter
{
    /**
     * Apply consistent formatting to markdown content.
     */
    public static function format(string $content): string
    {
        // Normalize line endings (CRLF → LF, CR → LF)
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // A "# " line inside a fence is code, not a heading.
        return Fences::outside($content, function (string $markdown): string {
            // Ensure blank line before and after markdown headings
            $spaced = preg_replace('/(?<!\n)\n(#{1,4} )/m', "\n\n$1", $markdown);
            $spaced = preg_replace('/^(#{1,4} .+)\n(?!\n)/m', "$1\n\n", (string) $spaced);

            // Collapse multiple consecutive empty lines into a single empty line
            return (string) preg_replace('/\n{3,}/', "\n\n", (string) $spaced);
        });
    }
}
