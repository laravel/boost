<?php

declare(strict_types=1);

namespace Laravel\Boost\Rules;

use Symfony\Component\Yaml\Yaml;
use Throwable;

class RuleFrontmatter
{
    /**
     * @return array{paths: array<int, string>, body: string}
     */
    public static function parse(string $content): array
    {
        $content = (string) preg_replace('/\R/', "\n", $content);

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
