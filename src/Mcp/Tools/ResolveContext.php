<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Mcp\Ace\ContextResolver;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ResolveContext extends Tool
{
    protected string $description = 'Load context by resolving one or more slices and/or bundles in a single call. Pass slice IDs with optional parameters, and/or bundle IDs (prefixed with @). Use boost-manifest to see available slices and bundles first. This batches multiple data sources into one response, eliminating the need for multiple tool calls.';

    public function __construct(protected ContextResolver $resolver) {}

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slices' => $schema->object()
                ->description('Map of slice IDs to their parameters. Use {} for no params. Example: {"db-schema": {"filter": "users"}, "app-info": {}}'),
            'bundles' => $schema->array()
                ->items($schema->string()->description('Bundle ID (e.g., "@database-work", "@debug")'))
                ->description('List of bundle IDs to expand and resolve'),
        ];
    }

    public function handle(Request $request): Response
    {
        $slices = $request->get('slices', []);
        $bundles = $request->get('bundles', []);

        if (empty($slices) && empty($bundles)) {
            return Response::error('At least one slice or bundle must be specified. Use boost-manifest to see available options.');
        }

        // Normalize slices: ensure each value is an array of params
        $normalizedSlices = [];
        foreach ($slices as $id => $params) {
            $normalizedSlices[$id] = is_array($params) ? $params : [];
        }

        $results = $this->resolver->resolve($normalizedSlices, $bundles);

        return Response::text($this->resolver->format($results));
    }
}
