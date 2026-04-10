<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\McpServer;
use Laravel\Boost\Install\ThirdPartyPackage;

use function Pest\testDirectory;

test('discover returns packages with valid structure', function (): void {
    $packages = ThirdPartyPackage::discover();

    expect($packages)->toBeInstanceOf(Collection::class);

    foreach ($packages as $key => $package) {
        expect($package)->toBeInstanceOf(ThirdPartyPackage::class)
            ->and($key)->toBe($package->name)
            ->and($package->hasGuidelines || $package->hasSkills || $package->hasMcp)->toBeTrue(
                "Package {$package->name} should have at least guidelines, skills, or mcp"
            );
    }
});

test('discover includes packages with only mcp configuration', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['acme/mcp-only-package' => '^1.0'],
    ]));

    $mcpDir = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'mcp-only-package', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpDir);
    file_put_contents($mcpDir.DIRECTORY_SEPARATOR.'mcp.json', json_encode([
        'servers' => [
            ['name' => 'acme-server', 'command' => 'node', 'args' => ['server.js']],
        ],
    ]));

    $packages = ThirdPartyPackage::discover();

    expect($packages->has('acme/mcp-only-package'))->toBeTrue();

    $pkg = $packages->get('acme/mcp-only-package');
    expect($pkg->hasMcp)->toBeTrue()
        ->and($pkg->hasGuidelines)->toBeFalse()
        ->and($pkg->hasSkills)->toBeFalse()
        ->and($pkg->mcpServers())->toHaveCount(1)
        ->and($pkg->mcpServers()->first())->toBeInstanceOf(McpServer::class)
        ->and($pkg->mcpServers()->first()->name)->toBe('acme-server');
})->afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});

test('discover parses mcp.json from fixture directory', function (): void {
    $fixturePath = testDirectory('Fixtures/vendor-mcp');

    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['acme/acme-package' => '^1.0'],
    ]));

    $vendorDir = base_path(implode(DIRECTORY_SEPARATOR, ['vendor', 'acme', 'acme-package']));
    $mcpDir = $vendorDir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, ['resources', 'boost', 'mcp']);
    File::ensureDirectoryExists($mcpDir);

    $fixtureMcpJson = $fixturePath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, [
        'acme-package', 'resources', 'boost', 'mcp', 'mcp.json',
    ]);
    copy($fixtureMcpJson, $mcpDir.DIRECTORY_SEPARATOR.'mcp.json');

    $packages = ThirdPartyPackage::discover();

    expect($packages->has('acme/acme-package'))->toBeTrue();

    $pkg = $packages->get('acme/acme-package');
    expect($pkg->hasMcp)->toBeTrue()
        ->and($pkg->mcpServers())->toHaveCount(2);

    $names = $pkg->mcpServers()->map(fn (McpServer $s) => $s->name)->toArray();
    expect($names)->toContain('acme-package-mcp')
        ->toContain('acme-package-remote-mcp');
})->afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});

test('discover records warning for invalid json in mcp.json', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['acme/bad-json-package' => '^1.0'],
    ]));

    $mcpDir = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'bad-json-package', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpDir);
    file_put_contents($mcpDir.DIRECTORY_SEPARATOR.'mcp.json', 'not valid json {{{');

    $packages = ThirdPartyPackage::discover();

    expect($packages->has('acme/bad-json-package'))->toBeTrue();

    $pkg = $packages->get('acme/bad-json-package');
    expect($pkg->hasMcp)->toBeFalse()
        ->and($pkg->mcpServers())->toBeEmpty()
        ->and($pkg->warnings())->not->toBeEmpty()
        ->and($pkg->warnings()[0])->toContain('acme/bad-json-package')
        ->and($pkg->warnings()[0])->toContain('Invalid JSON');
})->afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});

test('discover records warning when mcp.json has no servers key', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['acme/no-servers-package' => '^1.0'],
    ]));

    $mcpDir = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'no-servers-package', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpDir);
    file_put_contents($mcpDir.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['tools' => []]));

    $packages = ThirdPartyPackage::discover();

    $pkg = $packages->get('acme/no-servers-package');
    expect($pkg->hasMcp)->toBeFalse()
        ->and($pkg->warnings())->not->toBeEmpty()
        ->and($pkg->warnings()[0])->toContain("'servers' array");
})->afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});

test('discover records warning for invalid server entry missing name', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['acme/invalid-entry-package' => '^1.0'],
    ]));

    $mcpDir = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'invalid-entry-package', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpDir);
    file_put_contents($mcpDir.DIRECTORY_SEPARATOR.'mcp.json', json_encode([
        'servers' => [
            ['command' => 'node'],  // missing name
        ],
    ]));

    $packages = ThirdPartyPackage::discover();

    $pkg = $packages->get('acme/invalid-entry-package');
    expect($pkg->hasMcp)->toBeFalse()
        ->and($pkg->warnings())->not->toBeEmpty()
        ->and($pkg->warnings()[0])->toContain('Skipping server entry');
})->afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});
