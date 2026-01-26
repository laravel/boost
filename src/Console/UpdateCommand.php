<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    public function handle(Config $config): int
    {
        if (! $config->isValid() || empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
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
}
