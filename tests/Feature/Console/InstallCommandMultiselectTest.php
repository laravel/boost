<?php

declare(strict_types=1);

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

test('multiselect returns keys for associative array', function (): void {
    // Mock the prompt to simulate user selecting options
    // Note: mcp_server is already selected by default, so we don't toggle it
    Prompt::fake([
        Key::DOWN,       // Move to second option (ai_guidelines)
        Key::SPACE,      // Select second option
        Key::ENTER,      // Submit
    ]);

    $result = \Laravel\Prompts\multiselect(
        label: 'What shall we install?',
        options: [
            'mcp_server' => 'Boost MCP Server',
            'ai_guidelines' => 'Package AI Guidelines',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ],
        default: ['mcp_server']
    );

    // Assert that we get the keys, not the values
    expect($result)->toBeArray();
    expect($result)->toHaveCount(2, 'Should have 2 items selected');
    expect($result)->toContain('mcp_server');
    expect($result)->toContain('ai_guidelines');
    expect($result)->not->toContain('Boost MCP Server');
    expect($result)->not->toContain('Package AI Guidelines');
})->skipOnWindows();

test('multiselect returns values for indexed array', function (): void {
    Prompt::fake([
        Key::SPACE,      // Select first option
        Key::DOWN,       // Move to second option
        Key::SPACE,      // Select second option
        Key::ENTER,      // Submit
    ]);

    $result = \Laravel\Prompts\multiselect(
        label: 'Select options',
        options: ['Option 1', 'Option 2', 'Option 3'],
        default: []
    );

    // For indexed arrays, it returns the actual values
    expect($result)->toBeArray();
    expect($result)->toContain('Option 1');
    expect($result)->toContain('Option 2');
})->skipOnWindows();

test('multiselect behavior matches install command expectations', function (): void {
    // Test the exact same structure used in InstallCommand::selectBoostFeatures()
    // Note: mcp_server and ai_guidelines are already selected by default
    Prompt::fake([
        Key::DOWN,       // Move to ai_guidelines (already selected)
        Key::DOWN,       // Move to style_guidelines
        Key::SPACE,      // Select style_guidelines
        Key::ENTER,      // Submit
    ]);

    $toInstallOptions = [
        'mcp_server' => 'Boost MCP Server',
        'ai_guidelines' => 'Package AI Guidelines (i.e. Framework, Inertia, Pest)',
        'style_guidelines' => 'Laravel Style AI Guidelines',
    ];

    $result = \Laravel\Prompts\multiselect(
        label: 'What shall we install?',
        options: $toInstallOptions,
        default: ['mcp_server', 'ai_guidelines'],
        required: true,
        hint: 'Style guidelines are best for new projects',
    );

    // Verify we get keys that can be used with in_array checks
    expect($result)->toBeArray();
    expect($result)->toHaveCount(3); // All 3 selected (2 default + 1 added)

    // These are the exact checks used in InstallCommand
    expect(in_array('mcp_server', $result, true))->toBeTrue();
    expect(in_array('ai_guidelines', $result, true))->toBeTrue();
    expect(in_array('style_guidelines', $result, true))->toBeTrue();

    // Verify it doesn't contain the display values
    expect(in_array('Boost MCP Server', $result, true))->toBeFalse();
    expect(in_array('Package AI Guidelines (i.e. Framework, Inertia, Pest)', $result, true))->toBeFalse();
})->skipOnWindows();
