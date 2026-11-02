<?php

use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Boost\Mcp\Tools\SearchDocs;

it('can discover available tools', function (): void {
    $tools = ToolRegistry::getAvailableTools();

    expect($tools)->toBeArray()
        ->and($tools)->toContain(SearchDocs::class);
});

it('can check if the tool is allowed', function (): void {
    expect(ToolRegistry::isToolAllowed(SearchDocs::class))->toBeTrue()
        ->and(ToolRegistry::isToolAllowed('NonExistentTool'))->toBeFalse();
});

it('can get tool names', function (): void {
    $tools = ToolRegistry::getToolNames();

    expect($tools)->toBeArray()
        ->and($tools)->toHaveKey('SearchDocs')
        ->and($tools['SearchDocs'])->toBe(SearchDocs::class);
});

it('can clear cache', function (): void {
    // First call caches the results
    $tools1 = ToolRegistry::getAvailableTools();

    // Clear cache
    ToolRegistry::clearCache();

    // Second call should work fine
    $tools2 = ToolRegistry::getAvailableTools();

    expect($tools1)->toEqual($tools2);
});
