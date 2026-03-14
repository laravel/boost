<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    /** @var string */
    protected $signature = 'boost:update
        {--discover : Discover and prompt for newly available guidelines and skills}';

    public function handle(Config $config): int
    {
        if (! $config->isValid() || empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        if ($this->shouldDiscover()) {
            $this->discoverNewContent($config);
        }

        $guidelines = $config->getGuidelines();
        $hasSkills = $config->hasSkills();

        if (! $guidelines && ! $hasSkills) {
            return self::SUCCESS;
        }

        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $hasSkills,
        ]);

        $this->info('Boost guidelines and skills updated successfully.');

        return self::SUCCESS;
    }

    protected function discoverNewContent(Config $config): void
    {
        $newPackages = $this->resolveNewPackages($config);

        if ($newPackages->isNotEmpty()) {
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

    protected function shouldDiscover(): bool
    {
        return (bool) $this->option('discover');
    }
}

