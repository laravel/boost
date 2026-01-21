<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    public function handle(Config $config): void
    {
        $guidelines = $config->getGuidelines();
        $hasSkills = $config->hasSkills();

        if (! $guidelines && ! $hasSkills) {
            return;
        }

        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $hasSkills,
        ]);

        $this->components->info('Boost guidelines and skills updated successfully.');
    }
}
