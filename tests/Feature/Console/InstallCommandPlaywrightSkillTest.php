<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use JMac\Testing\Double;
use Laravel\Boost\Support\Config;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-playwright-skill-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    config(['boost.agents.claude_code.mcp_config_path' => $this->tempBasePath.'/.mcp.json']);

    $project = Double::for(ProjectManager::class, override: true)->instance();
    $this->app->instance(ProjectManager::class, $project);

    mockProjectPackages($project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@playwright/test', '1.64.1')->setDirect(),
        rosterPackage('playwright-core', '1.64.1', path: fixture('vendor-packages/playwright-core')),
    ]));

    (new Config)->setAgents(['claude_code']);
});

afterEach(function (): void {
    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('installs the skills playwright ships when the integration is selected', function (): void {
    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(file_exists($this->tempBasePath.'/.claude/skills/playwright-cli/SKILL.md'))->toBeTrue()
        ->and(file_exists($this->tempBasePath.'/.claude/skills/playwright-cli/references/playwright-tests.md'))->toBeTrue()
        ->and(file_exists($this->tempBasePath.'/.claude/skills/playwright-trace/SKILL.md'))->toBeTrue()
        ->and(is_dir($this->tempBasePath.'/.claude/skills/playwright-component-testing'))->toBeFalse()
        ->and((new Config)->getSkills())->toContain('playwright-cli', 'playwright-trace')
        ->and((new Config)->getPlaywright())->toBeTrue();
});

it('does not install the playwright skills when the integration is not selected', function (): void {
    (new Config)->setPlaywright(false);

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(is_dir($this->tempBasePath.'/.claude/skills/playwright-cli'))->toBeFalse()
        ->and((new Config)->getPlaywright())->toBeFalse();
});
