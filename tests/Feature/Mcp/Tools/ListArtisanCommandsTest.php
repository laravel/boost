<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ListArtisanCommands;
use Laravel\Mcp\Request;

test('it returns list of artisan commands', function (): void {
    $tool = new ListArtisanCommands;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content): void {
            expect($content)->toBeArray()
                ->and($content)->not->toBeEmpty();

            // Check that it contains some basic Laravel commands
            $commandNames = array_column($content, 'name');
            expect($commandNames)->toContain('migrate')
                ->and($commandNames)->toContain('make:model')
                ->and($commandNames)->toContain('route:list');

            // Check the structure of each command
            foreach ($content as $command) {
                expect($command)->toHaveKey('name')
                    ->and($command)->toHaveKey('description')
                    ->and($command['name'])->toBeString()
                    ->and($command['description'])->toBeString();
            }

            // Check that commands are sorted alphabetically
            $sortedNames = $commandNames;
            sort($sortedNames);
            expect($commandNames)->toBe($sortedNames);
        });
});
