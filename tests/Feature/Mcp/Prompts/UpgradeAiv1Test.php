<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\UpgradeAiv1\UpgradeAiV1;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->prompt = new UpgradeAiV1;
});

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-ai-v1');
});

test('it registers only for pre-1.0 installations', function (): void {
    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection([rosterPackage('laravel/ai', '0.11.2')]));

    expect($this->prompt->shouldRegister($project))->toBeTrue();

    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection([rosterPackage('laravel/ai', '1.0.0')]));

    expect($this->prompt->shouldRegister($project))->toBeFalse();
});

test('it returns a valid response', function (): void {
    expect($this->prompt->handle())
        ->isToolResult()
        ->toolHasNoError();
});

test('it contains core upgrade content', function (): void {
    expect($this->prompt->handle())->isToolResult()
        ->toolTextContains('Laravel AI 0.11 to 1.0 Upgrade Specialist')
        ->toolTextContains('Conversation messages store steps')
        ->toolTextContains('Agent middleware wraps each generation step')
        ->toolTextContains('Token usage is reported inclusively')
        ->toolTextContains('The AWS SDK is no longer installed by default')
        ->toolTextContains('Backfill Migration')
        ->toolTextContains('usingVercelDataProtocol');
});

test('it properly compiles blade assist helpers', function (): void {
    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('composer require laravel/ai:^1.0')
        ->toContain('composer show laravel/ai')
        ->toContain('composer require aws/aws-sdk-php')
        ->toContain('php artisan migrate')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('{{ $assist');
});
