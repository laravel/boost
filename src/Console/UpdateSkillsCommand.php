<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use const DIRECTORY_SEPARATOR;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Skills\Remote\GitHubRepository;
use Laravel\Boost\Skills\Remote\GitHubSkillProvider;
use Laravel\Boost\Skills\Remote\InstalledSkill;
use Laravel\Boost\Skills\Remote\RemoteSkill;
use Laravel\Boost\Support\SkillsLock;
use Laravel\Prompts\Terminal;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\spin;

class UpdateSkillsCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'boost:update-skills
        {--force : Update skills without confirmation}
        {--sync : Force sync all skills from GitHub (overwrites local changes)}';

    protected $description = 'Update installed skills to their latest versions from GitHub';

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
            $this->error('No skills lock file found. Please install skills first using boost:add-skill.');

            return self::FAILURE;
        }

        $installedSkills = $lock->getSkills();

        if ($installedSkills === []) {
            $this->warn('No skills found in lock file.');

            return self::SUCCESS;
        }

        return $this->updateSkills($installedSkills);
    }

    /**
     * @param  array<string, InstalledSkill>  $installedSkills
     */
    protected function updateSkills(array $installedSkills): int
    {
        $isSync = $this->isSyncMode();
        $skillsToUpdate = $this->findOutdatedSkills($installedSkills);
        $skillCount = $skillsToUpdate->count();

        if ($skillsToUpdate->isEmpty()) {
            $this->info($isSync ? 'All skills are already in sync.' : 'All skills are up to date.');

            return self::SUCCESS;
        }

        if ($isSync) {
            $this->info("Found {$skillCount} skill(s) to sync from GitHub (--sync):");
        } else {
            $this->info("Found {$skillCount} skill(s) to update:");
        }

        grid($skillsToUpdate->keys()->values()->toArray());

        if (! $this->option('force') && stream_isatty(STDIN)) {
            $label = $isSync
                ? "Sync {$skillCount} skill(s) from GitHub?"
                : "Update {$skillCount} skill(s)?";

            if (! confirm(label: $label)) {
                return self::SUCCESS;
            }
        }

        $results = $this->downloadUpdatedSkills($skillsToUpdate);

        $this->displayUpdateResults($results);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, InstalledSkill>  $installedSkills
     * @return Collection<string, array{installed: InstalledSkill, remote: RemoteSkill}>
     */
    protected function findOutdatedSkills(array $installedSkills): Collection
    {
        $message = $this->isSyncMode()
            ? 'Checking skills to sync...'
            : 'Checking for updates...';

        return spin(
            callback: function () use ($installedSkills): Collection {
                $result = collect($installedSkills)
                    ->filter(fn (InstalledSkill $skill): bool => $skill->sourceType === 'github')
                    ->map(fn (InstalledSkill $skill): ?array => $this->checkSkillUpdate($skill))
                    ->filter(fn (?array $item): bool => $item !== null);

                return $result->keyBy(fn (array $data): string => $data['installed']->name);
            },
            message: $message
        );
    }

    /**
     * @return array{installed: InstalledSkill, remote: RemoteSkill}|null
     */
    protected function checkSkillUpdate(InstalledSkill $installed): ?array
    {
        try {
            $fetcher = $this->fetcherForInstalledSkill($installed);
            $availableSkills = $fetcher->discoverSkills();

            $remoteSkill = $availableSkills->get($installed->name);

            if ($remoteSkill === null) {
                return null;
            }

            $remoteHash = $fetcher->getSkillHash($remoteSkill);

            if ($remoteHash !== null && $remoteHash === $installed->hash) {
                if (! $this->isSyncMode()) {
                    return null;
                }

                if (! $fetcher->hasLocalChanges($remoteSkill, $this->skillTargetPath($remoteSkill))) {
                    return null;
                }
            }

            return ['installed' => $installed, 'remote' => $remoteSkill];
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @param  Collection<string, array{installed: InstalledSkill, remote: RemoteSkill}>  $skillsToUpdate
     * @return array{updated: array<int, string>, failed: array<string, string>}
     */
    protected function downloadUpdatedSkills(Collection $skillsToUpdate): array
    {
        $message = $this->isSyncMode()
            ? 'Syncing skills...'
            : 'Updating skills...';

        return spin(
            callback: fn (): array => $this->performUpdate($skillsToUpdate),
            message: $message
        );
    }

    /**
     * @param  Collection<string, array{installed: InstalledSkill, remote: RemoteSkill}>  $skillsToUpdate
     * @return array{updated: array<int, string>, failed: array<string, string>}
     */
    protected function performUpdate(Collection $skillsToUpdate): array
    {
        $action = $this->isSyncMode() ? 'Syncing' : 'Updating';
        $results = ['updated' => [], 'failed' => []];
        $lock = new SkillsLock;

        foreach ($skillsToUpdate as $data) {
            $installed = $data['installed'];
            $remoteSkill = $data['remote'];

            try {
                $fetcher = $this->fetcherForInstalledSkill($installed);
                $targetPath = $this->skillTargetPath($remoteSkill);

                $this->line("  {$action} {$remoteSkill->name} from {$installed->source}...");

                if (is_dir($targetPath)) {
                    File::deleteDirectory($targetPath);
                }

                $downloadResult = $fetcher->downloadSkill($remoteSkill, $targetPath);

                if ($downloadResult) {
                    $results['updated'][] = $remoteSkill->name;

                    $newHash = $fetcher->getSkillHash($remoteSkill);

                    if ($newHash !== null) {
                        $lock->addSkill(new InstalledSkill(
                            name: $remoteSkill->name,
                            source: $installed->source,
                            sourceType: $installed->sourceType,
                            hash: $newHash,
                        ));
                    }
                } else {
                    $results['failed'][$remoteSkill->name] = 'Download failed';
                }
            } catch (RuntimeException $e) {
                $results['failed'][$remoteSkill->name] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * @param  array{updated: array<int, string>, failed: array<string, string>}  $results
     */
    protected function displayUpdateResults(array $results): void
    {
        $isSync = $this->isSyncMode();

        if ($results['updated'] !== []) {
            $this->info($isSync ? 'Skills synced:' : 'Skills updated:');

            grid($results['updated']);
        }

        if ($results['failed'] !== []) {
            $this->error($isSync ? 'Some skills failed to sync:' : 'Some skills failed to update:');

            grid(array_keys($results['failed']));
        }

        $this->info($isSync ? 'Skills sync completed.' : 'Skills update completed.');
    }

    protected function isSyncMode(): bool
    {
        return (bool) $this->option('sync');
    }

    protected function fetcherForInstalledSkill(InstalledSkill $installed): GitHubSkillProvider
    {
        return new GitHubSkillProvider(GitHubRepository::fromInput($installed->source));
    }

    protected function skillTargetPath(RemoteSkill $skill): string
    {
        return base_path($this->defaultSkillsPath.DIRECTORY_SEPARATOR.$skill->name);
    }

    protected function displayHeader(): void
    {
        $this->terminal->initDimensions();
        $this->displayBoostHeader('Skills Update', config('app.name'));
    }
}
