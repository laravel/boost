<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Prompts\Terminal;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;

class RemoveSkillCommand extends Command
{
    use DisplayHelper;

    /** @var string */
    protected $signature = 'boost:rm-skill
        {skills?* : The names of the skills to remove}
        {--force : Force removal without confirmation}';

    /** @var string */
    protected $description = 'Remove installed skills';

    protected string $defaultSkillsPath = '.ai/skills';

    public function __construct(private readonly Terminal $terminal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->displayHeader();

        $skillNames = $this->argument('skills');

        if (empty($skillNames)) {
            $skillNames = $this->promptForSkills();
        }

        if (empty($skillNames)) {
            $this->warn('No skills selected.');

            return self::SUCCESS;
        }

        $validSkills = $this->validateSkills($skillNames);

        if ($validSkills === []) {
            $this->error('No valid skills to remove.');

            return self::FAILURE;
        }

        if (! $this->confirmRemoval($validSkills)) {
            return self::SUCCESS;
        }

        return $this->removeSkills($validSkills);
    }

    /**
     * @return array<int, string>
     */
    protected function promptForSkills(): array
    {
        $skillsPath = base_path($this->defaultSkillsPath);

        if (! File::exists($skillsPath)) {
            $this->error('No skills directory found.');

            return [];
        }

        $skills = collect(File::directories($skillsPath))
            ->map(fn (string $path): string => basename($path))
            ->mapWithKeys(fn (string $name): array => [$name => $name])
            ->all();

        if (empty($skills)) {
            $this->error('No skills installed.');

            return [];
        }

        return multiselect(
            label: 'Which skills would you like to remove?',
            options: $skills,
            required: true
        );
    }

    /**
     * @param  array<int, string>  $skillNames
     * @return array<int, string>
     */
    protected function validateSkills(array $skillNames): array
    {
        $validSkills = [];

        foreach ($skillNames as $name) {
            $skillPath = base_path($this->defaultSkillsPath.DIRECTORY_SEPARATOR.$name);

            if (File::exists($skillPath)) {
                $validSkills[] = $name;
            } else {
                $this->warn("Skill '{$name}' not found.");
            }
        }

        return $validSkills;
    }

    /**
     * @param  array<int, string>  $skills
     */
    protected function confirmRemoval(array $skills): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $count = count($skills);
        $skillList = implode(', ', $skills);
        $label = $count === 1
            ? "Are you sure you want to remove the '{$skills[0]}' skill?"
            : "Are you sure you want to remove these {$count} skills? ({$skillList})";

        if (! confirm($label)) {
            $this->info('Removal cancelled.');

            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $skills
     */
    protected function removeSkills(array $skills): int
    {
        $removed = [];
        $failed = [];

        foreach ($skills as $name) {
            $skillPath = base_path($this->defaultSkillsPath.DIRECTORY_SEPARATOR.$name);

            $success = spin(
                callback: fn () => File::deleteDirectory($skillPath),
                message: "Removing skill '{$name}'..."
            );

            if ($success) {
                $removed[] = $name;
            } else {
                $failed[] = $name;
                $this->error("Failed to remove skill '{$name}'.");
            }
        }

        if ($removed !== []) {
            $this->info('Skills removed: '.implode(', ', $removed));
            $this->runBoostUpdate();
            $this->displayOutro('Enjoy the boost 🚀', terminalWidth: $this->terminal->cols());
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    protected function displayHeader(): void
    {
        $this->terminal->initDimensions();
        $this->displayBoostHeader('Skill', config('app.name'));
    }

    protected function runBoostUpdate(): void
    {
        $this->callSilently(UpdateCommand::class);
    }
}
