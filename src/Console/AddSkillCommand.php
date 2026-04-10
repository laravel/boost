<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use const DIRECTORY_SEPARATOR;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Skills\Remote\AuditResult;
use Laravel\Boost\Skills\Remote\GitHubRepository;
use Laravel\Boost\Skills\Remote\GitHubSkillProvider;
use Laravel\Boost\Skills\Remote\RemoteSkill;
use Laravel\Boost\Skills\Remote\SkillAuditor;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
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
        {--force : Overwrite existing skills}
        {--skip-audit : Skip security audit}';

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
                message: "Fetching skills from {$this->repository->source()}..."
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

        $skillsToInstall = $this->skillsToInstall($selectedSkills);

        if ($skillsToInstall->isEmpty()) {
            return self::SUCCESS;
        }

        if (! $this->runAuditBeforeInstall($skillsToInstall)) {
            return self::SUCCESS;
        }

        $results = $this->downloadSkills($skillsToInstall);

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
            return $this->availableSkills->only($skillOptions);
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

        return $this->availableSkills->only($selected);
    }

    /**
     * @param  Collection<string, RemoteSkill>  $skills
     */
    protected function skillsToInstall(Collection $skills): Collection
    {
        [$existingSkills, $newSkills] = $skills->partition(
            fn (RemoteSkill $skill): bool => $this->skillExists($skill)
        );

        if ($existingSkills->isEmpty() || $this->shouldUpdateExisting($existingSkills)) {
            return $skills;
        }

        return $newSkills;
    }

    /**
     * @param  Collection<string, RemoteSkill>  $existingSkills
     */
    protected function shouldUpdateExisting(Collection $existingSkills): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! stream_isatty(STDIN)) {
            return false;
        }

        return confirm(
            label: "Update {$existingSkills->count()} existing skill(s)?",
        );
    }

    protected function skillExists(RemoteSkill $skill): bool
    {
        return is_dir($this->skillTargetPath($skill));
    }

    protected function skillTargetPath(RemoteSkill $skill): string
    {
        return base_path($this->defaultSkillsPath.DIRECTORY_SEPARATOR.$skill->name);
    }

    /**
     * @param  Collection<string, RemoteSkill>  $skills
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function downloadSkills(Collection $skills): array
    {
        return spin(
            callback: fn (): array => $this->addSkills($skills),
            message: 'Downloading skills...'
        );
    }

    /**
     * @param  Collection<string, RemoteSkill>  $skills
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function addSkills(Collection $skills): array
    {
        $results = ['installedNames' => [], 'failedDetails' => []];
        $config = new Config;

        foreach ($skills as $skill) {
            $targetPath = $this->skillTargetPath($skill);

            if ($this->skillExists($skill)) {
                File::deleteDirectory($targetPath);
            }

            try {
                if ($this->fetcher->downloadSkill($skill, $targetPath)) {
                    $results['installedNames'][] = $skill->name;

                    $config->trackSkill(
                        repository: $this->repository->source(),
                        skillName: $skill->name,
                        sourceType: 'github',
                        computedHash: $this->fetcher->getSkillHash($skill),
                    );
                } else {
                    $results['failedDetails'][$skill->name] = 'Download failed';
                }
            } catch (RuntimeException $e) {
                $results['failedDetails'][$skill->name] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * @param  Collection<string, RemoteSkill>  $selectedSkills
     */
    protected function runAuditBeforeInstall(Collection $selectedSkills): bool
    {
        if ($this->option('skip-audit')) {
            return true;
        }

        $skillNames = $selectedSkills->keys()->values()->all();

        /** @var array<string, array<int, AuditResult>> $auditResults */
        $auditResults = spin(
            callback: fn (): array => (new SkillAuditor)->audit(
                $this->repository->source(),
                $skillNames,
            ),
            message: 'Running security audit...',
        );

        if (! $this->hasRiskySkills($auditResults)) {
            return true;
        }

        $this->displayAuditResults($auditResults, $skillNames);

        if (! stream_isatty(STDIN)) {
            return true;
        }

        return confirm('Do you want to install these skills?');
    }

    /**
     * @param  array<string, array<int, AuditResult>>  $auditResults
     * @param  array<int, string>  $skillNames
     */
    protected function displayAuditResults(array $auditResults, array $skillNames): void
    {
        $partnerKeys = collect($auditResults)
            ->flatMap(fn (array $results): array => array_map(fn (AuditResult $auditResult): string => $auditResult->partner, $results))
            ->unique()
            ->values()
            ->all();

        $headers = array_merge(['Skill'], array_map(ucfirst(...), $partnerKeys));

        $rows = [];

        foreach ($skillNames as $skillName) {
            $partnerResults = $auditResults[$skillName] ?? [];
            $partnerMap = collect($partnerResults)->keyBy(fn (AuditResult $auditResult): string => $auditResult->partner);

            $row = [$skillName];

            foreach ($partnerKeys as $partnerKey) {
                $row[] = $partnerMap->has($partnerKey)
                    ? $this->colorizeRisk($partnerMap->get($partnerKey))
                    : '—';
            }

            $rows[] = $row;
        }

        note('Security Audit');
        table($headers, $rows);
    }

    /**
     * @param  array<string, array<int, AuditResult>>  $auditResults
     */
    protected function hasRiskySkills(array $auditResults): bool
    {
        return collect($auditResults)
            ->flatten()
            ->contains(fn (AuditResult $result): bool => $result->risk->weight() >= 3);
    }

    protected function colorizeRisk(AuditResult $result): string
    {
        return match ($result->risk->color()) {
            'red' => $this->red($result->risk->label()),
            'yellow' => $this->yellow($result->risk->label()),
            'green' => $this->green($result->risk->label()),
            default => $this->dim($result->risk->label()),
        };
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
