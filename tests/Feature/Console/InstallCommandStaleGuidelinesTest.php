<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Boost\Support\Config;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-stale-guidelines-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    file_put_contents($this->tempBasePath.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
        ],
    ]));

    $this->app->instance(ProjectManager::class, new ProjectManager);
    $this->app->instance(RuleRepository::class, new RuleRepository($this->tempBasePath.'/.ai/rules'));
    $this->app->instance(GuidelineConfig::class, new GuidelineConfig);

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);

    config(['boost.enforce_tests' => false]);
    config(['boost.agents.claude_code.guidelines_path' => $this->tempBasePath.'/CLAUDE.md']);
});

afterEach(function (): void {
    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('warns about a legacy guideline file that still has a Boost block', function (): void {
    $legacy = "# Copilot\n\n<laravel-boost-guidelines>\nold\n</laravel-boost-guidelines>\n";
    File::makeDirectory($this->tempBasePath.'/.github');
    file_put_contents($this->tempBasePath.'/.github/copilot-instructions.md', $legacy);

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->expectsOutputToContain('These files have a Boost guidelines block that was not updated')
        ->expectsOutputToContain('.github/copilot-instructions.md')
        ->doesntExpectOutputToContain('  - CLAUDE.md')
        ->assertSuccessful();

    expect(file_get_contents($this->tempBasePath.'/.github/copilot-instructions.md'))->toBe($legacy);
})->skipOnWindows();

it('warns when the updated guideline file has more than one Boost block', function (): void {
    file_put_contents($this->tempBasePath.'/CLAUDE.md', "<laravel-boost-guidelines>\nfirst\n</laravel-boost-guidelines>\n\n<laravel-boost-guidelines>\nsecond\n</laravel-boost-guidelines>\n");

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->expectsOutputToContain('These files have a Boost guidelines block that was not updated')
        ->expectsOutputToContain('  - CLAUDE.md')
        ->assertSuccessful();

    expect(file_get_contents($this->tempBasePath.'/CLAUDE.md'))->toContain("second\n</laravel-boost-guidelines>");
})->skipOnWindows();

it('does not warn when only the updated guideline file has a Boost block', function (): void {
    file_put_contents($this->tempBasePath.'/AGENTS.md', "# Agents\n\nNo Boost block here.\n");

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->doesntExpectOutputToContain('was not updated')
        ->assertSuccessful();

    $this->artisan('boost:install', ['--guidelines' => true, '--no-interaction' => true])
        ->doesntExpectOutputToContain('was not updated')
        ->assertSuccessful();
})->skipOnWindows();

it('warns about legacy guideline files on update', function (): void {
    file_put_contents($this->tempBasePath.'/GEMINI.md', "<laravel-boost-guidelines>\nold\n</laravel-boost-guidelines>\n");

    $this->artisan('boost:update', ['--no-discover' => true])
        ->expectsOutputToContain('These files have a Boost guidelines block that was not updated')
        ->expectsOutputToContain('  - GEMINI.md')
        ->assertSuccessful();

    expect(file_exists($this->tempBasePath.'/GEMINI.md'))->toBeTrue();
})->skipOnWindows();
