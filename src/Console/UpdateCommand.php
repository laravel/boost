<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:update', 'Updates Laravel Boost Guidelines to the latest version.')]
class UpdateCommand extends Command
{
    public function handle(): void
    {
        $this->callSilently(InstallCommand::class, [
            '--no-interaction' => true,
        ]);

        $this->components->info('Boost Guidelines have been updated successfully.');
    }
}
