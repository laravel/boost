<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class Fences
{
    /**
     * Fenced code blocks per CommonMark, so inline backtick runs are not paired.
     */
    private const PATTERN = '/^ {0,3}(?<fence>`{3,}|~{3,})[^\n]*\n.*?(?:^ {0,3}\k<fence>[`~]*[ \t]*$|\z)/ms';

    /**
     * Run a transformation over the markdown outside its fenced code blocks.
     *
     * @param  callable(string): string  $callback
     */
    public static function outside(string $content, callable $callback): string
    {
        $fences = [];

        $masked = preg_replace_callback(self::PATTERN, function (array $matches) use (&$fences): string {
            $placeholder = "\0".count($fences)."\0";
            $fences[$placeholder] = $matches[0];

            return $placeholder;
        }, $content);

        if ($masked === null) {
            return $content;
        }

        return str_replace(array_keys($fences), array_values($fences), $callback($masked));
    }
}
