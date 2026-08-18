<?php

declare(strict_types=1);

namespace Laravel\Boost\Rules;

use Symfony\Component\Yaml\Yaml;
use Throwable;

class RuleFrontmatter
{
    /**
     * Matches CRLF, CR and LF only.
     *
     * `\R` cannot be used here: without the `u` modifier PCRE matches byte by
     * byte and `\R` also covers NEL (0x85), which is a continuation byte of
     * many multi-byte UTF-8 characters. The `u` modifier is not an option
     * either, since it makes preg_replace() return null for invalid UTF-8.
     */
    public const NEWLINE_PATTERN = '/\r\n|\n|\r/';

    /**
     * @return array{paths: array<int, string>, body: string}
     */
    public static function parse(string $content): array
    {
        $content = (string) preg_replace(self::NEWLINE_PATTERN, "\n", $content);

        if (preg_match('/^\s*---\s*\n(.*?)\n---\s*\n?/s', $content, $matches) !== 1) {
            return ['paths' => [], 'body' => $content];
        }

        try {
            $meta = Yaml::parse($matches[1]);
        } catch (Throwable) {
            return ['paths' => [], 'body' => $content];
        }

        $paths = array_values(array_filter(
            (array) (is_array($meta) ? ($meta['paths'] ?? []) : []),
            is_string(...)
        ));

        return [
            'paths' => $paths,
            'body' => substr($content, strlen($matches[0])),
        ];
    }
}
