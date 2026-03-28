<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class CommandNormalizer
{
    /**
     * Normalize a command string into a command and args array.
     *
     * Absolute paths (starting with / on Unix or a drive letter on Windows)
     * are never split, as they may contain spaces (e.g. macOS "Application Support").
     *
     * @param  array<int, string>  $args
     * @return array{command: string, args: array<int, string>}
     */
    public static function normalize(string $command, array $args = []): array
    {
        if (str_starts_with($command, '/') || preg_match('#^[a-zA-Z]:[/\\\\]#', $command)) {
            return [
                'command' => $command,
                'args' => $args,
            ];
        }

        $parts = str($command)->explode(' ');

        return [
            'command' => $parts->first(),
            'args' => $parts->skip(1)->values()->merge($args)->all(),
        ];
    }
}
