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

        $count = count($failures->all());
        $message = $count === 1
            ? '1 skill is not valid and was skipped. Its existing registration was left unchanged:'
            : sprintf('%d skills are not valid and were skipped. Their existing registrations were left unchanged:', $count);

        $this->newLine();
        $this->warn($message);

        foreach ($failures->all() as $path => $failure) {
            $this->line('  - '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).': '.$failure);
        }
    }
}
