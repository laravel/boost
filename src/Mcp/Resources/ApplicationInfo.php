<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo as ApplicationInfoTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ApplicationInfo extends Resource
{
    public function __construct(protected ToolExecutor $toolExecutor) {}

    /**
     * The resource's description.
     */
    protected string $description = 'Comprehensive application information including PHP version, Laravel version, database engine, all installed packages with their versions, and all Eloquent models in the application.';

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
        $response = $this->toolExecutor->execute(ApplicationInfoTool::class);

        if ($response->isError()) {
            return $response; // Return the error response directly
        }

        $data = json_decode((string) $response->content(), true);

        if (! $data) {
            return Response::error('Error parsing application information');
        }

        return Response::json($data);
    }
}
