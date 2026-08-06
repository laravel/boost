<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    use ConfirmableTrait;

    /** @var string */
    protected $signature = 'boost:update
        {--discover : Discover and prompt for newly available guidelines and skills (default)}
        {--no-discover : Skip discovering and prompting for newly available guidelines and skills}
        {--ignore-skills : Skip updating the skills directory}
        {--fresh : Delete and rebuild each selected agent\'s skills directory from Boost and .ai/skills}
        {--force : Run a fresh skill rebuild without confirmation}';

    public function handle(Config $config): int
    {
        if (! $config->isValid() || empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $guidelines = $config->getGuidelines();
        $hasSkills = ! $this->option('ignore-skills') && ($config->hasSkills() || is_dir(base_path('.ai/skills')));
        $fresh = $hasSkills && (bool) $this->option('fresh');

        if ($fresh && ! $this->confirmToProceed(
            "A fresh skill rebuild will recreate each selected agent's skills directory from scratch. Custom skills must be stored in [.ai/skills] as described in the documentation.",
            true,
        )) {
            return self::FAILURE;
        }

        if (! $this->option('no-discover')) {
            $this->discoverNewContent($config);
        }

        if (! $guidelines && ! $hasSkills) {
            return self::SUCCESS;
        }

        $arguments = [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $hasSkills,
            '--fresh' => $fresh,
        ];

        if ($fresh) {
            $arguments['--force'] = true;
        }

        $this->callSilently(InstallCommand::class, $arguments);

        $this->info('Boost guidelines and skills updated successfully.');

        return self::SUCCESS;
    }

    protected function discoverNewContent(Config $config): void
    {
        $newPackages = $this->resolveNewPackages($config);

        if ($newPackages->isEmpty()) {
            return;
        }

        if (! $this->input->isInteractive()) {
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
    protected function resolveNewPackages(Config $config): Collection
    {
        $configuredPackages = $config->getPackages();

        return ThirdPartyPackage::discover()
            ->filter(fn (ThirdPartyPackage $pkg, string $name): bool => ! in_array($name, $configuredPackages, true));
    }
}
