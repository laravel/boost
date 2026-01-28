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
        // Ensure blank line before and after markdown headings
        $content = preg_replace('/(?<!\n)\n(#{1,4} )/m', "\n\n$1", $content);
        $content = preg_replace('/(#{1,4} .+)\n(?!\n)/m', "$1\n\n", (string) $content);

        // Collapse multiple consecutive empty lines into a single empty line
        $content = preg_replace('/\n{3,}/', "\n\n", (string) $content);

        return $content;
    }
}
