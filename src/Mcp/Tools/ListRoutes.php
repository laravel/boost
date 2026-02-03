<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Concerns\InteractsWithArtisanCommand;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListRoutes extends Tool
{
    use InteractsWithArtisanCommand;

    /**
     * The tool's description.
     */
    protected string $description = 'List all available routes defined in the application, including Folio routes if used';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'method' => $schema->string()->description('Filter the routes by HTTP method (e.g., GET, POST, PUT, DELETE).'),
            'action' => $schema->string()->description('Filter the routes by controller action (e.g., UserController@index, ChatController, show).'),
            'name' => $schema->string()->description('Filter the routes by route name (no wildcards supported).'),
            'domain' => $schema->string()->description('Filter the routes by domain.'),
            'path' => $schema->string()->description('Only show routes matching the given path pattern.'),
            'except_path' => $schema->string()->description('Do not display the routes matching the given path pattern.'),
            'except_vendor' => $schema->boolean()->description('Do not display routes defined by vendor packages.'),
            'only_vendor' => $schema->boolean()->description('Only display routes defined by vendor packages.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
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
            $value = $request->get($argKey);

            if (! empty($value)) {
                if (is_bool($value)) {
                    $options['--'.$cliOption] = true;
                } else {
                    $sanitizedValue = str_replace(['*', '?'], '', $value);

                    if (filled($sanitizedValue)) {
                        $options['--'.$cliOption] = $sanitizedValue;
                    }
                }
            }
        }

        $routesOutput = $this->callArtisanCommand('route:list', $options);

        // If Folio is installed, include folio routes (JSON to prevent hanging)
        if (class_exists('Laravel\\Folio\\FolioRoutes')) {
            $routesOutput .= "\n\n=== FOLIO ROUTES (JSON) ===\n\n";

            $folioOptions = $options;
            $folioOptions['--json'] = true; // Ensure non-interactive json output

            $routesOutput .= $this->callArtisanCommand('folio:list', $folioOptions);
        }

        return Response::text($routesOutput);
    }
}
