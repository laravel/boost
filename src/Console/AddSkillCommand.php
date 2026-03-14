<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use const DIRECTORY_SEPARATOR;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Skills\Remote\GitHubRepository;
use Laravel\Boost\Skills\Remote\GitHubSkillProvider;
use Laravel\Boost\Skills\Remote\RemoteSkill;
use Laravel\Prompts\Terminal;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class AddSkillCommand extends Command
{
    use DisplayHelper;

    /** @var string */
    protected $signature = 'boost:add-skill
        {repo? : GitHub repository (owner/repo or full URL)}
        {--list : List available skills}
        {--all : Install all skills}
        {--skill=* : Specific skills to install}
        {--force : Overwrite existing skills}';

    /** @var string */
    protected $description = 'Add skills from a remote GitHub repository';

    protected GitHubRepository $repository;

    protected GitHubSkillProvider $fetcher;

    /** @var Collection<string, RemoteSkill> */
    protected Collection $availableSkills;

    protected string $defaultSkillsPath = '.ai/skills';

    public function __construct(private readonly Terminal $terminal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->displayHeader();

        if (! $this->initializeRepository()) {
            return self::FAILURE;
        }

        if (! $this->discoverAvailableSkills()) {
            return self::FAILURE;
        }

        return $this->handleAction();
    }

    protected function initializeRepository(): bool
    {
        $repository = $this->parseRepository();

        if (! $repository instanceof GitHubRepository) {
            return false;
        }

        $this->repository = $repository;
        $this->fetcher = new GitHubSkillProvider($this->repository);

        return true;
    }

    protected function discoverAvailableSkills(): bool
    {
        try {
            $this->availableSkills = spin(
                callback: fn (): Collection => $this->fetcher->discoverSkills(),
                message: "Fetching skills from {$this->repository->fullName()}..."
            );
        } catch (RuntimeException $runtimeException) {
            $this->error($runtimeException->getMessage());

            return false;
        }

        if ($this->availableSkills->isEmpty()) {
            $this->error('No valid skills are found in the repository.');

            return false;
        }

        return true;
    }

    protected function handleAction(): int
    {
        if ($this->option('list')) {
            return $this->displaySkillsTable();
        }

        return $this->installSkills();
    }

    protected function parseRepository(): ?GitHubRepository
    {
        $input = $this->argument('repo') ??
            text(
                label: 'Which GitHub repository would you like to fetch skills from?',
                placeholder: 'owner/repo or GitHub URL',
                required: true,
                validate: function (string $value): ?string {
                    try {
                        GitHubRepository::fromInput($value);

                        return null;
                    } catch (InvalidArgumentException $invalidArgumentException) {
                        return $invalidArgumentException->getMessage();
                    }
                },
                hint: 'e.g., vercel-labs/agent-skills or https://github.com/owner/repo'
            );

        return GitHubRepository::fromInput($input);
    }

    protected function displayHeader(): void
    {
        $this->terminal->initDimensions();
        $this->displayBoostHeader('Skill', config('app.name'));
    }

    protected function displaySkillsTable(): int
    {
        note("Found {$this->availableSkills->count()} available skills");

        grid($this->availableSkills->keys()->sort()->values()->toArray());

        return self::SUCCESS;
    }

    protected function installSkills(): int
    {
        $selectedSkills = $this->selectSkills();

        if ($selectedSkills->isEmpty()) {
            $this->warn('No skills are selected.');

            return self::SUCCESS;
        }

        $results = $this->downloadSkills($selectedSkills);

        if ($results['installedNames'] !== []) {
            $this->info('Skills installed:');

            grid($results['installedNames']);

            $this->runBoostUpdate();
            $this->showOutro();
        }

        if ($results['failedDetails'] !== []) {
            $this->error('Some skills failed to install:');

            grid(array_keys($results['failedDetails']));
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, RemoteSkill>
     */
    protected function selectSkills(): Collection
    {
        if ($this->option('all')) {
            return $this->availableSkills;
        }

        /** @var array<int, string> $skillOptions */
        $skillOptions = $this->option('skill');

        if ($skillOptions !== []) {
            return $this->availableSkills->filter(
                fn (RemoteSkill $skill): bool => in_array($skill->name, $skillOptions, true)
            );
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which skills would you like to install?',
            options: $this->availableSkills
                ->mapWithKeys(fn (RemoteSkill $skill): array => [$skill->name => $skill->name])
                ->toArray(),
            scroll: 10,
            required: true,
            hint: 'Use --all to install all skills at once',
        );

        return $this->availableSkills->filter(
            fn (RemoteSkill $skill): bool => in_array($skill->name, $selected, true)
        );
    }

    /**
     * @param  Collection<string, RemoteSkill>  $skills
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function downloadSkills(Collection $skills): array
    {
        $force = $this->option('force');
        $absoluteSkillsPath = base_path($this->defaultSkillsPath);

        $existingSkills = $skills->filter(fn (RemoteSkill $skill): bool => is_dir($absoluteSkillsPath.DIRECTORY_SEPARATOR.$skill->name));
        $shouldUpdateExisting = $force;

        if ($existingSkills->isNotEmpty() && ! $force && stream_isatty(STDIN)) {
            $count = $existingSkills->count();
            $shouldUpdateExisting = confirm(
                label: "Update {$count} existing skill(s) ?",
            );
        }

        return spin(
            callback: fn (): array => $this->addSkills($skills, $absoluteSkillsPath, $shouldUpdateExisting),
            message: 'Downloading skills...'
        );
    }

    /**
     * @param  Collection<string, RemoteSkill>  $skills
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function addSkills(Collection $skills, string $absoluteSkillsPath, bool $shouldUpdateExisting): array
    {
        $results = ['installedNames' => [], 'failedDetails' => []];

        foreach ($skills as $skill) {
            $targetPath = $absoluteSkillsPath.DIRECTORY_SEPARATOR.$skill->name;
            $exists = is_dir($targetPath);

            if ($exists && ! $shouldUpdateExisting) {
                continue;
            }

            if ($exists) {
                File::deleteDirectory($targetPath);
            }

            try {
                if ($this->fetcher->downloadSkill($skill, $targetPath)) {
                    $results['installedNames'][] = $skill->name;
                } else {
                    $results['failedDetails'][$skill->name] = 'Download failed';
                }
            } catch (RuntimeException $e) {
                $results['failedDetails'][$skill->name] = $e->getMessage();
            }
        }

        return $results;
    }

    protected function runBoostUpdate(): void
    {
        $this->callSilently(UpdateCommand::class);
    }

    protected function showOutro(): void
    {
        $this->displayOutro('Enjoy the boost 🚀', terminalWidth: $this->terminal->cols());
    }
}
