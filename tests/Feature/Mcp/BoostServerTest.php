<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('exposes an icon in the MCP server metadata', function (): void {
    $server = new Boost(new FakeTransporter);

    $serverInfo = $server->createContext()->implementation->toArray();

    expect($serverInfo['icons'])->toHaveCount(1)
        ->and($serverInfo['icons'][0]['src'])->toStartWith('data:image/svg+xml;base64,')
        ->and($serverInfo['icons'][0]['mimeType'])->toBe('image/svg+xml')
        ->and($serverInfo['icons'][0]['sizes'])->toBe(['40x40']);

    $decoded = base64_decode((string) str($serverInfo['icons'][0]['src'])->after('base64,'), true);

    expect($decoded)
        ->toBe((string) file_get_contents(__DIR__.'/../../../resources/icons/boost.svg'))
        ->toStartWith('<svg');
});
