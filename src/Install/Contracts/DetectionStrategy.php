<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Contracts;

use Laravel\Boost\Install\Enums\Platform;

interface DetectionStrategy
{
    /**
     * Detect if the application is installed on the machine.
     *
     * @param  array{command?:string, basePath?:string, files?:array<string>, paths?:array<string>}  $config
     */
    public function detect(array $config, ?Platform $platform = null): bool;
}
