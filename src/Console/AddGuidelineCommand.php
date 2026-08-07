<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use const DIRECTORY_SEPARATOR;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Guidelines\Remote\GitHubGuidelineProvider;
use Laravel\Boost\Guidelines\Remote\RemoteGuideline;
use Laravel\Boost\Support\GitHubRepository;
use Laravel\Prompts\Terminal;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class AddGuidelineCommand extends Command
{
    use DisplayHelper;

    /** @var string */
    protected $signature = 'boost:add-guideline
        {repo? : GitHub repository (owner/repo or full URL)}
        {--list : List available guidelines}
        {--all : Install all guidelines}
        {--guideline=* : Specific guidelines to install}
        {--force : Overwrite existing guidelines}';

    /** @var string */
    protected $description = 'Add guidelines from a remote GitHub repository';

    protected GitHubRepository $repository;

    protected GitHubGuidelineProvider $fetcher;

    /** @var Collection<string, RemoteGuideline> */
    protected Collection $availableGuidelines;

    protected string $defaultGuidelinesPath = '.ai/guidelines';

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

        if (! $this->discoverAvailableGuidelines()) {
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
        $this->fetcher = new GitHubGuidelineProvider($this->repository);

        return true;
    }

    protected function handleAction(): int
    {
        if ($this->option('list')) {
            return $this->displayGuidelinesTable();
        }

        return $this->installGuidelines();
    }

    protected function discoverAvailableGuidelines(): bool
    {
        try {
            $this->availableGuidelines = spin(
                callback: fn (): Collection => $this->fetcher->discoverGuidelines(),
                message: "Fetching guidelines from {$this->repository->source()}..."
            );
        } catch (RuntimeException $runtimeException) {
            $this->error($runtimeException->getMessage());

            return false;
        }

        if ($this->availableGuidelines->isEmpty()) {
            $this->error('No Markdown guidelines were found. Remote guidelines must be stored beneath guidelines/ or an explicitly provided repository path.');

            return false;
        }

        return true;
    }

    protected function parseRepository(): ?GitHubRepository
    {
        $input = $this->argument('repo') ??
            text(
                label: 'Which GitHub repository would you like to fetch guidelines from?',
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
                hint: 'e.g., owner/repo or https://github.com/owner/repo/tree/main/path/to/guidelines'
            );

        return GitHubRepository::fromInput($input);
    }

    protected function displayHeader(): void
    {
        $this->terminal->initDimensions();
        $this->displayBoostHeader('Guideline', config('app.name'));
    }

    protected function displayGuidelinesTable(): int
    {
        note("Found {$this->availableGuidelines->count()} available guidelines");

        grid($this->availableGuidelines->keys()->sort()->values()->toArray());

        return self::SUCCESS;
    }

    protected function installGuidelines(): int
    {
        $selectedGuidelines = $this->selectGuidelines();

        if ($selectedGuidelines->isEmpty()) {
            $this->warn('No guidelines are selected.');

            return self::SUCCESS;
        }

        $guidelinesToInstall = $this->guidelinesToInstall($selectedGuidelines);

        if ($guidelinesToInstall->isEmpty()) {
            return self::SUCCESS;
        }

        $results = $this->downloadGuidelines($guidelinesToInstall);

        if ($results['installedNames'] !== []) {
            $this->info('Guidelines installed:');

            grid($results['installedNames']);

            $this->runBoostUpdate();
            $this->showOutro();
        }

        if ($results['failedDetails'] !== []) {
            $this->error('Some guidelines failed to install:');

            grid(array_keys($results['failedDetails']));
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, RemoteGuideline>
     */
    protected function selectGuidelines(): Collection
    {
        if ($this->option('all')) {
            return $this->availableGuidelines;
        }

        /** @var array<int, string> $guidelineOptions */
        $guidelineOptions = $this->option('guideline');

        if ($guidelineOptions !== []) {
            return $this->availableGuidelines->only($guidelineOptions);
        }

        if (! $this->input->isInteractive()) {
            return collect();
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which guidelines would you like to install?',
            options: $this->availableGuidelines
                ->mapWithKeys(fn (RemoteGuideline $guideline): array => [$guideline->name => $guideline->name])
                ->toArray(),
            scroll: 10,
            required: true,
            hint: 'Use --all to install all guidelines at once',
        );

        return $this->availableGuidelines->only($selected);
    }

    /**
     * @param  Collection<string, RemoteGuideline>  $guidelines
     * @return Collection<string, RemoteGuideline>
     */
    protected function guidelinesToInstall(Collection $guidelines): Collection
    {
        [$existingGuidelines, $newGuidelines] = $guidelines->partition(
            fn (RemoteGuideline $guideline): bool => $this->guidelineExists($guideline)
        );

        if ($existingGuidelines->isEmpty() || $this->shouldUpdateExisting($existingGuidelines)) {
            return $guidelines;
        }

        $this->warn("Skipped {$existingGuidelines->count()} existing guideline(s). Use --force to overwrite them.");

        return $newGuidelines;
    }

    /**
     * @param  Collection<string, RemoteGuideline>  $existingGuidelines
     */
    protected function shouldUpdateExisting(Collection $existingGuidelines): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: "Overwrite {$existingGuidelines->count()} existing guideline(s)?",
        );
    }

    protected function guidelineExists(RemoteGuideline $guideline): bool
    {
        return file_exists($this->guidelineTargetPath($guideline));
    }

    protected function guidelineTargetPath(RemoteGuideline $guideline): string
    {
        return base_path(
            $this->defaultGuidelinesPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $guideline->relativePath)
        );
    }

    /**
     * @param  Collection<string, RemoteGuideline>  $guidelines
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function downloadGuidelines(Collection $guidelines): array
    {
        return spin(
            callback: fn (): array => $this->addGuidelines($guidelines),
            message: 'Downloading guidelines...'
        );
    }

    /**
     * @param  Collection<string, RemoteGuideline>  $guidelines
     * @return array{installedNames: array<int, string>, failedDetails: array<string, string>}
     */
    protected function addGuidelines(Collection $guidelines): array
    {
        $results = ['installedNames' => [], 'failedDetails' => []];

        foreach ($guidelines as $guideline) {
            try {
                if ($this->fetcher->downloadGuideline($guideline, base_path($this->defaultGuidelinesPath))) {
                    $results['installedNames'][] = $guideline->name;
                } else {
                    $results['failedDetails'][$guideline->name] = 'Download failed';
                }
            } catch (RuntimeException $runtimeException) {
                $results['failedDetails'][$guideline->name] = $runtimeException->getMessage();
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
