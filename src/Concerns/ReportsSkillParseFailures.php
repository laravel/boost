<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Support\Str;
use Laravel\Boost\Support\SkillParseFailures;

trait ReportsSkillParseFailures
{
    protected function reportSkillParseFailures(): void
    {
        $failures = app(SkillParseFailures::class);

        if ($failures->isEmpty()) {
            return;
        }

        $entries = $failures->all();

        $this->newLine();
        $this->warn(sprintf(
            'Skipped %d %s with invalid or incomplete frontmatter, leaving existing %s unchanged:',
            count($entries),
            Str::plural('skill', $entries),
            Str::plural('registration', $entries)
        ));

        foreach ($entries as $entry) {
            $location = Str::after($this->normalizeSeparators($entry['path']), $this->normalizeSeparators(base_path()).'/');

            $this->line(rtrim(sprintf('  - %s (%s): %s', $entry['name'], $location, $entry['reason']), ': '));
        }
    }

    private function normalizeSeparators(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }
}
