<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use const DIRECTORY_SEPARATOR;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Skills\Remote\InstalledSkill;
use Laravel\Boost\Support\SkillsLock;
use Laravel\Prompts\Terminal;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\multiselect;

class RemoveSkillCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'boost:remove-skill';

    protected $description = 'Remove installed skills';

    protected string $defaultSkillsPath = '.ai/skills';

    public function __construct(private readonly Terminal $terminal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->displayHeader();

        $lock = new SkillsLock;

        if (! $lock->isValid()) {
            $this->error('No skills lock file found.');

            return self::FAILURE;
        }

        $installedSkills = $lock->getSkills();

        if ($installedSkills === []) {
            $this->warn('No skills found in lock file.');

            return self::SUCCESS;
        }

        $selectedSkills = $this->selectSkills($installedSkills);

        if ($selectedSkills === []) {
            $this->info('No skills selected.');

            return self::SUCCESS;
        }

        if (stream_isatty(STDIN) && ! confirm(label: 'Remove '.count($selectedSkills).' skill(s)?')) {
            return self::SUCCESS;
        }

        $removedSkills = $this->removeSkills($lock, $selectedSkills);

        $this->info('Skills removed:');
        grid($removedSkills);

        $this->info('Skill removal completed.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, InstalledSkill>  $installedSkills
     * @return array<int, string>
     */
    protected function selectSkills(array $installedSkills): array
    {
        $options = [];

        foreach ($installedSkills as $name => $skill) {
            $options[$name] = sprintf('%s (%s)', $name, $skill->source);
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Select skills to remove',
            options: $options,
            scroll: 10,
            required: false,
            hint: 'Leave empty to cancel',
        );

        return $selected;
    }

    /**
     * @param  array<int, string>  $selectedSkills
     * @return array<int, string>
     */
    protected function removeSkills(SkillsLock $lock, array $selectedSkills): array
    {
        $removedSkills = [];

        foreach ($selectedSkills as $skillName) {
            $lock->removeSkill($skillName);

            $path = base_path($this->defaultSkillsPath.DIRECTORY_SEPARATOR.$skillName);

            if (is_dir($path)) {
                File::deleteDirectory($path);
            }

            $removedSkills[] = $skillName;
        }

        sort($removedSkills);

        return $removedSkills;
    }

    protected function displayHeader(): void
    {
        $this->terminal->initDimensions();
        $this->displayBoostHeader('Skill Remove', config('app.name'));
    }
}
