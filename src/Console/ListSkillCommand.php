<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Install\SkillComposer;

use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class ListSkillCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'boost:list-skill';

    protected $description = 'List all available skills in the current project';

    public function handle(SkillComposer $skillComposer): int
    {
        $skills = $skillComposer->skills();

        if ($skills->isEmpty()) {
            $this->info('No skills available in this project.');

            return self::SUCCESS;
        }

        $this->displayBoostHeader('Skills', config('app.name'));

        $count = $skills->count();
        note("Found {$count} skill".($count === 1 ? '' : 's'));

        $this->displaySkillsTable($skills);

        return self::SUCCESS;
    }

    protected function displaySkillsTable(Collection $skills): void
    {
        $rows = $skills
            ->sortBy(fn ($skill) => $skill->name)
            ->map(fn ($skill): array => $skill->custom
                ? [$this->dim($skill->name.'*'), $this->yellow('local')]
                : [$skill->name, $this->dim($skill->package)]
            )
            ->values()
            ->toArray();

        table(
            headers: ['Skill', 'Source'],
            rows: $rows
        );
    }
}
