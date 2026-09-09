<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Concerns\ReportsSkillParseFailures;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Boost\Support\SkillParseFailures;
use Laravel\Roster\ProjectManager;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    use ReportsSkillParseFailures;

    /** @var string */
    protected $signature = 'boost:update
        {--discover : Discover and prompt for newly available guidelines and skills (default)}
        {--no-discover : Skip discovering and prompting for newly available guidelines and skills}
        {--ignore-skills : Skip updating the skills directory}';

    public function handle(Config $config, ProjectManager $project): int
    {
        app(SkillParseFailures::class)->flush();

        if (! $config->isValid()) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $guidelines = $config->getGuidelines();
        $hasSkills = ! $this->option('ignore-skills') && ($config->hasSkills() || is_dir(base_path('.ai/skills')));

        if (! $guidelines && ! $hasSkills) {
            return self::SUCCESS;
        }

        if (empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        if (! $this->option('no-discover')) {
            $this->discoverNewContent($config, $project);
        }

        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $hasSkills,
        ]);

        $this->reportSkillParseFailures();

        $this->info('Boost guidelines and skills updated successfully.');

        return self::SUCCESS;
    }

    protected function discoverNewContent(Config $config, ProjectManager $project): void
    {
        $newPackages = $this->resolveNewPackages($config, $project);

        if ($newPackages->isEmpty()) {
            return;
        }

        if (! $this->input->isInteractive() || $this->runningAsComposerScript()) {
            return;
        }

        /** @var array<int, string> $selectedPackages */
        $selectedPackages = multiselect(
            label: 'New packages with guidelines/skills discovered! Which would you like to add?',
            options: $newPackages
                ->mapWithKeys(fn (ThirdPartyPackage $pkg, string $name): array => [$name => $pkg->displayLabel()])
                ->toArray(),
            scroll: 10,
            required: false,
            hint: 'Select packages to include their guidelines and skills',
        );

        if ($selectedPackages !== []) {
            $config->setPackages(array_merge($config->getPackages(), $selectedPackages));
        }
    }

    /**
     * @return Collection<string, ThirdPartyPackage>
     */
    protected function resolveNewPackages(Config $config, ProjectManager $project): Collection
    {
        $configuredPackages = $config->getPackages();

        return ThirdPartyPackage::discover($project)
            ->filter(fn (ThirdPartyPackage $pkg, string $name): bool => ! in_array($name, $configuredPackages, true));
    }

    /**
     * Composer sets COMPOSER_DEV_MODE for the entire install/update run, including
     * post-update-cmd scripts, so prompting there would block an unattended `composer update`.
     */
    protected function runningAsComposerScript(): bool
    {
        return getenv('COMPOSER_DEV_MODE') !== false;
    }
}
