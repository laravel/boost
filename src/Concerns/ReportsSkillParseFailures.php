<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Laravel\Boost\Support\SkillParseFailures;

trait ReportsSkillParseFailures
{
    protected function reportSkillParseFailures(): void
    {
        $failures = app(SkillParseFailures::class);

        if ($failures->isEmpty()) {
            return;
        }

        $names = $failures->skillNames();
        $message = count($names) === 1
            ? '1 skill has invalid YAML frontmatter and was skipped. Its existing registration was left unchanged:'
            : sprintf('%d skills have invalid YAML frontmatter and were skipped. Their existing registrations were left unchanged:', count($names));

        $this->newLine();
        $this->warn($message);

        foreach ($names as $name) {
            $this->line('  - '.$name);
        }
    }
}
