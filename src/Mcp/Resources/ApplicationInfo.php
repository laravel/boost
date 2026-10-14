<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Illuminate\Support\Facades\DB;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;
use Laravel\Roster\Package;
use Laravel\Roster\ProjectManager;

class ApplicationInfo extends Resource
{
    public function __construct(protected ProjectManager $project)
    {
        //
    }

    /**
     * The resource's description.
     */
    protected string $description = 'Comprehensive application information including PHP version, Laravel version, database engine, and all installed packages with their versions in the application.';

    /**
     * The resource's URI.
     */
    protected string $uri = 'file://instructions/application-info.md';

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'text/markdown';

    /**
     * Handle the resource request.
     */
    public function handle(): Response
    {
        return Response::json([
            'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => DB::connection()->getDriverName(),
            'packages' => $this->project->php()->packages()
                ->concat($this->project->js()->packages())
                ->map(fn (Package $package): array => [
                    'roster_name' => PackageRegistry::rosterName($package->name()),
                    'version' => $package->version(),
                    'package_name' => $package->name(),
                ]),
        ]);
    }
}
