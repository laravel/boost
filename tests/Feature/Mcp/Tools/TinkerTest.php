<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;
use Laravel\Tinker\TinkerServiceProvider;
use Orchestra\Testbench\Foundation\Application as Testbench;

use function Orchestra\Testbench\package_path;

beforeEach(function (): void {
    $result = Testbench::createVendorSymlink(base_path(), package_path('vendor'));
    $this->vendorSymlinkCreated = $result['TESTBENCH_VENDOR_SYMLINK'] ?? false;

    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->app);
    Application::setInstance($this->app);

    $this->app->register(TinkerServiceProvider::class);
});

afterEach(function (): void {
    if ($this->vendorSymlinkCreated ?? false) {
        Testbench::deleteVendorSymlink(base_path());
    }
});

test('executes code and returns output', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "Hello World";']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Hello World');
});

test('handles errors gracefully', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'invalid syntax here']));

    expect((string) $response->content())->toContain('Syntax error');
});

test('strips php tags from code', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '<?php echo "stripped"; ?>']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('stripped');
});

test('should register only in local environment', function (): void {
    $tool = new Tinker;

    app()->detectEnvironment(fn (): string => 'local');

    expect($tool->eligibleForRegistration())->toBeTrue();
});
