<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Prompts\Terminal;

use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class SkillListCommand extends Command
{
    use DisplayHelper;

    /** @var string */
    protected $signature = 'boost:skill-list
        {--json : Output as JSON}';

    /** @var string */
    protected $description = 'List all available skills in the current project';

    public function __construct(
        private readonly Terminal $terminal,
        private readonly SkillComposer $skillComposer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->terminal->initDimensions();

        $skills = $this->skillComposer->skills();

        if ($skills->isEmpty()) {
            $this->info('No skills available in this project.');

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->output->writeln(
                json_encode(
                    $skills->map(fn ($skill) => [
                        'name' => $skill->name,
                        'description' => $skill->description,
                        'package' => $skill->package,
                        'custom' => $skill->custom,
                    ])->values()->toArray(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );

            return self::SUCCESS;
        }

        $this->displayBoostHeader('Skills', config('app.name'));

        $count = $skills->count();
        note("Found {$count} skill".($count === 1 ? '' : 's'));

        $this->displaySkillsTable($skills);

        return self::SUCCESS;
    }

    protected function displaySkillsTable($skills): void
    {
        $rows = $skills
            ->sortBy(fn ($skill) => $skill->name)
            ->map(fn ($skill) => [
                $skill->custom ? $this->dim($skill->name.'*') : $skill->name,
                $skill->custom ? $this->yellow('local') : $this->dim($skill->package),
            ])
            ->values()
            ->toArray();

        table(
            headers: ['Skill', 'Source'],
            rows: $rows
        );

        $this->newLine();
        $this->line('  '.$this->dim('* = user-defined skill'));
    }
}
