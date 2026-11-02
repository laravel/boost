<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Roster\Package;
use Laravel\Roster\ProjectManager;

class PestAgent
{
    public const PACKAGE = 'pestphp/pest-plugin-agent';

    public const MINIMUM_PEST_MAJOR = 5;

    public const MINIMUM_PHP_VERSION = '8.4.0';

    public const INSTALL_TIMEOUT_SECONDS = 300;

    public function __construct(protected ProjectManager $project)
    {
        //
    }

    public function isInstallable(): bool
    {
        $pest = $this->project->php()->package(PackageRegistry::PEST);

        return $pest?->isDirect()
            && $pest->major() >= self::MINIMUM_PEST_MAJOR
            && version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')
            && ! $this->project->php()->package(self::PACKAGE) instanceof Package;
    }

    public function installCommand(bool $usesSail = false): string
    {
        $composer = config('boost.executable_paths.composer') ?: ($usesSail ? Sail::composerCommand() : 'composer');

        return "{$composer} require ".self::PACKAGE.' --dev';
    }

    /**
     * Scripts are skipped so a `boost:update` composer hook cannot re-enter the running install.
     */
    public function install(bool $usesSail = false): ProcessResult
    {
        $result = Process::path(base_path())
            ->timeout(self::INSTALL_TIMEOUT_SECONDS)
            ->run($this->installCommand($usesSail).' --no-scripts --no-interaction');

        if ($result->successful()) {
            $this->project->fresh();
        }

        return $result;
    }
}
