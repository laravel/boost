<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Concerns;

use Illuminate\Support\Str;

trait DerivesTitleFromMarkdown
{
    protected function title(string $body, string $fallback): string
    {
        if (preg_match('/^#+\s+(.+?)\s*$/m', $body, $matches) === 1) {
            return trim($matches[1]);
        }

        return Str::headline($fallback);
    }
}
