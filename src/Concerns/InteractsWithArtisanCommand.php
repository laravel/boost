<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Exception;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;

trait InteractsWithArtisanCommand
{
    protected function callArtisanCommand(string $command, array $options = []): string
    {
        $output = new BufferedOutput;

        try {
            $exitCode = Artisan::call($command, $options, $output);

            if ($exitCode !== Command::SUCCESS) {
                throw new RuntimeException(
                    "Artisan command '{$command}' failed: ".$output->fetch()
                );
            }

            return trim($output->fetch());
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new RuntimeException(
                "Artisan command '{$command}' failed: ".$e->getMessage(),
                previous: $e
            );
        }
    }
}
