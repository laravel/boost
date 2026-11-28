<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Laravel\Roster\ProjectManager;
use Laravel\Roster\ProjectScan;

use function Orchestra\Sidekick\working_path;

/**
 * Roster scans `base_path()` by default, which is Testbench's skeleton application when
 * running through the Testbench CLI. Scan the package's working path instead.
 */
class TestbenchProjectManager extends ProjectManager
{
    public function scan(?string $basePath = null): ProjectScan
    {
        return $basePath === null
            ? $this->cached = parent::scan(working_path())
            : parent::scan($basePath);
    }

    public function fresh(?string $basePath = null): ProjectScan
    {
        return $basePath === null
            ? $this->cached = parent::fresh(working_path())
            : parent::fresh($basePath);
    }
}
