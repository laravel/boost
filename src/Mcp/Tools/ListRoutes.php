<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Output\BufferedOutput;

#[IsReadOnly]
class ListRoutes extends Tool
{
    public function description(): string
    {
        return 'List all available routes defined in the application, including Folio routes if used';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        // Mirror the most common `route:list` options. All are optional.
        $schema->string('method')->description('Filter the routes by HTTP method (e.g., GET, POST, PUT, DELETE).')->required(false);
        $schema->string('action')->description('Filter the routes by controller action (e.g., UserController@index, ChatController, show).')->required(false);
        $schema->string('name')->description('Filter the routes by route name (no wildcards supported).')->required(false);
        $schema->string('domain')->description('Filter the routes by domain.')->required(false);
        $schema->string('path')->description('Only show routes matching the given path pattern.')->required(false);
        // Keys with hyphens are converted to underscores for PHP variable compatibility.
        $schema->string('except_path')->description('Do not display the routes matching the given path pattern.')->required(false);
        $schema->boolean('except_vendor')->description('Do not display routes defined by vendor packages.')->required(false);
        $schema->boolean('only_vendor')->description('Only display routes defined by vendor packages.')->required(false);

        return $schema;
    }

    /**
     * @param array<string> $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $optionMap = [
            'method' => 'method',
            'action' => 'action',
            'name' => 'name',
            'domain' => 'domain',
            'path' => 'path',
            'except_path' => 'except-path', // Convert underscore back to hyphen
            'except_vendor' => 'except-vendor',
            'only_vendor' => 'only-vendor',
        ];

        $options = [
            '--no-ansi' => true,
            '--no-interaction' => true,
        ];

        foreach ($optionMap as $argKey => $cliOption) {
            if (! empty($arguments[$argKey])) {
                $sanitizedValue = str_replace(['*', '?'], '', $arguments[$argKey]);
                if (filled($sanitizedValue)) {
                    $options['--'.$cliOption] = $sanitizedValue;
                }
            }
        }

        $routesOutput = $this->artisan('route:list', $options);

        // If Folio is installed, include folio routes (JSON to prevent hanging)
        if (class_exists('Laravel\\Folio\\FolioRoutes')) {
            $routesOutput .= "\n\n=== FOLIO ROUTES (JSON) ===\n\n";

            $folioOptions = $options;
            $folioOptions['--json'] = true; // Ensure non-interactive json output

            $routesOutput .= $this->artisan('folio:list', $folioOptions);
        }

        return ToolResult::text($routesOutput);
    }

    /**
     * @param array<string|bool> $options
     */
    private function artisan(string $command, array $options = []): string
    {
        $output = new BufferedOutput;
        $result = Artisan::call($command, $options, $output);
        if ($result !== CommandAlias::SUCCESS) {
            return 'Failed to list routes: '.$output->fetch();
        }

        return trim($output->fetch());
    }
}
