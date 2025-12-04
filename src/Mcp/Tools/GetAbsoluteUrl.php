<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetAbsoluteUrl extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Get the absolute URL for a given relative path or named route. If no arguments are provided, you will get the absolute URL for "/"';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('The relative URL/path (e.g. "/dashboard") to convert to an absolute URL.'),
            'route' => $schema->string()
                ->description('The named route to generate an absolute URL for (e.g. "home").'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $request->get('path');
        $routeName = $request->get('route');

        if ($path) {
            return Response::text(url($path));
        }

        if ($routeName) {
            return Response::text(route($routeName));
        }

        return Response::text(url('/'));
    }
}
