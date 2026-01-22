<?php

use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;

it('can discover available tools', function (): void {
    $tools = ToolRegistry::getAvailableTools();

    expect($tools)->toBeArray()
        ->and($tools)->toContain(ApplicationInfo::class);
});

it('can check if the tool is allowed', function (): void {
    expect(ToolRegistry::isToolAllowed(ApplicationInfo::class))->toBeTrue()
        ->and(ToolRegistry::isToolAllowed('NonExistentTool'))->toBeFalse();
});

it('can get tool names', function (): void {
    $tools = ToolRegistry::getToolNames();

    expect($tools)->toBeArray()
        ->and($tools)->toHaveKey('ApplicationInfo')
        ->and($tools['ApplicationInfo'])->toBe(ApplicationInfo::class);
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
