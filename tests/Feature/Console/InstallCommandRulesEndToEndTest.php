<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Boost\Support\Config;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-rules-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    file_put_contents($this->tempBasePath.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
        ],
        'packages-dev' => [
            ['name' => 'pestphp/pest', 'version' => 'v3.0.0'],
        ],
    ]));

    // BoostServiceProvider::register() skips its bindings under runningUnitTests(), so bind them as production would.
    $this->app->instance(Roster::class, Roster::scan($this->tempBasePath));
    $this->app->instance(RuleRepository::class, new RuleRepository($this->tempBasePath.'/.ai/rules'));
    $this->app->instance(GuidelineConfig::class, new GuidelineConfig);

    (new Config)->setAgents(['claude_code']);
    config(['boost.enforce_tests' => false]);
    config(['boost.agents.claude_code.guidelines_path' => $this->tempBasePath.'/CLAUDE.md']);
});

afterEach(function (): void {
    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('extracts path-scoped rules into .ai/rules/boost when running boost:install with rules enabled', function (): void {
    config(['boost.rules.enabled' => true]);

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->assertSuccessful();

    $managed = collect(File::glob($this->tempBasePath.'/.ai/rules/boost/*.md'));

    expect($managed)->not->toBeEmpty();

    $testsFile = $managed->first(fn (string $path): bool => str_contains(File::get($path), 'tests/**'));

    expect($testsFile)->not->toBeNull()
        ->and(File::get($testsFile))
        ->toContain('paths:')
        ->toContain('Pest')
        ->toContain('php artisan test --compact');

    $claude = File::get($this->tempBasePath.'/CLAUDE.md');

    expect($claude)
        ->not->toContain('php artisan test --compact')
        ->toContain('.ai/rules/index.md');
});

it('re-inlines everything and removes the managed directory when rules are disabled', function (): void {
    config(['boost.rules.enabled' => true]);

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(File::isDirectory($this->tempBasePath.'/.ai/rules/boost'))->toBeTrue();

    config(['boost.rules.enabled' => false]);

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(File::isDirectory($this->tempBasePath.'/.ai/rules/boost'))->toBeFalse();

    $claude = File::get($this->tempBasePath.'/CLAUDE.md');

    expect($claude)
        ->toContain('php artisan test --compact')
        ->toContain('Pest')
        ->toContain('### Model Creation');
});

it('falls back to inlining scoped content with a warning when rule syncing fails', function (): void {
    config(['boost.rules.enabled' => true]);

    $this->mock(RuleRepository::class, function ($mock): void {
        $mock->shouldReceive('syncManaged')->andThrow(new RuntimeException('disk full'));
        $mock->shouldReceive('clearManaged')->andReturn(false);
    });

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Could not write path-scoped rules to .ai/rules/boost')
        ->assertSuccessful();

    expect(File::isDirectory($this->tempBasePath.'/.ai/rules/boost'))->toBeFalse();

    $claude = File::get($this->tempBasePath.'/CLAUDE.md');

    expect($claude)
        ->toContain('php artisan test --compact')
        ->toContain('Pest')
        ->toContain('### Model Creation');
});

it('aborts instead of re-inlining when both rule syncing and cleanup fail', function (): void {
    config(['boost.rules.enabled' => true]);

    $this->mock(RuleRepository::class, function ($mock): void {
        $mock->shouldReceive('syncManaged')->andThrow(new RuntimeException('disk full'));
        $mock->shouldReceive('clearManaged')->andThrow(new RuntimeException('locked directory'));
    });

    expect(fn (): int => $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])->run())
        ->toThrow(RuntimeException::class, 'could not clear .ai/rules/boost');
});
