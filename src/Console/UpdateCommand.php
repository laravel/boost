<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines to the latest guidance')]
class UpdateCommand extends Command
{
    public function handle(): void
    {
        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
            '--ignore-mcp' => true,
        ]);

        $this->components->info('Boost guidelines updated successfully.');
    }
}
