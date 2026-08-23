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

        $count = count($failures->all());

        $this->newLine();
        $this->warn(sprintf('Kept %d %s whose SKILL.md frontmatter is not valid YAML — fix the frontmatter so its metadata can be read:', $count, Str::plural('skill', $count)));

        foreach ($failures->all() as $path => $message) {
            $this->line('  - '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).': '.$message);
        }
    }
}
