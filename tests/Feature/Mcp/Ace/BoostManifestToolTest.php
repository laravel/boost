<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\Manifest;
use Laravel\Boost\Mcp\Tools\BoostManifest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

test('boost-manifest returns a text response with all slices', function (): void {
    $tool = app(BoostManifest::class);

    $response = $tool->handle(new Request([]));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->isError())->toBeFalse();

    $text = getResponseText($response, $tool);

    expect($text)->toContain('Available context')
        ->and($text)->toContain('resolve-context');
});

test('boost-manifest lists all 14 dynamic slices', function (): void {
    $tool = app(BoostManifest::class);
    $text = getResponseText($tool->handle(new Request([])), $tool);

    $expectedSlices = [
        'app-info', 'db-schema', 'db-connections', 'db-query', 'routes',
        'artisan-commands', 'config-keys', 'env-vars', 'get-config',
        'absolute-url', 'last-error', 'browser-logs', 'log-entries', 'search-docs',
    ];

    foreach ($expectedSlices as $slice) {
        expect($text)->toContain($slice);
    }
});

test('boost-manifest lists all bundles', function (): void {
    $tool = app(BoostManifest::class);
    $text = getResponseText($tool->handle(new Request([])), $tool);

    expect($text)->toContain('@database-work')
        ->and($text)->toContain('@testing')
        ->and($text)->toContain('@debug')
        ->and($text)->toContain('@new-feature');
});

test('boost-manifest lists guideline slices', function (): void {
    $tool = app(BoostManifest::class);
    $text = getResponseText($tool->handle(new Request([])), $tool);

    expect($text)->toContain('[guidelines]')
        ->and($text)->toContain('foundation');
});

test('boost-manifest includes param indicators for parameterized slices', function (): void {
    $tool = app(BoostManifest::class);
    $text = getResponseText($tool->handle(new Request([])), $tool);

    // db-schema has summary, filter and database params
    expect($text)->toContain('(param: summary, filter, database)');
});

function getResponseText(Response $response, $tool): string
{
    $content = $response->content();

    return $content->toTool($tool)['text'] ?? '';
}
