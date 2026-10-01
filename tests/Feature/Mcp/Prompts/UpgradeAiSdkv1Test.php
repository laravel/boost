<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\UpgradeAiSdkv1\UpgradeAiSdkV1;

beforeEach(function (): void {
    $this->prompt = new UpgradeAiSdkV1;
});

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-ai-sdk-v1');
});

test('it returns a valid response', function (): void {
    expect($this->prompt->handle())
        ->isToolResult()
        ->toolHasNoError();
});

test('it contains core upgrade content', function (): void {
    expect($this->prompt->handle())->isToolResult()
        ->toolTextContains('Laravel AI 0.11 to 1.0 Upgrade Specialist')
        ->toolTextContains('Laravel MCP 1.0 Is Required')
        ->toolTextContains('Conversation Messages Now Store Steps')
        ->toolTextContains('Agent Middleware Wraps Each Generation Step')
        ->toolTextContains('Token Usage Includes All Tokens')
        ->toolTextContains('The AWS SDK Is No Longer Installed By Default')
        ->toolTextContains('Resumed Turns Fold Into The Message They Paused On')
        ->toolTextContains('Failed Turns Are Recorded')
        ->toolTextContains('Latest Conversations Are Scoped To The Agent')
        ->toolTextContains('Gemini Uses The Interactions API')
        ->toolTextContains('usingVercelDataProtocol')
        ->toolTextContains('MessageStatus')
        ->toolTextContains('https://github.com/laravel/mcp/blob/main/UPGRADE.md');
});

test('it properly compiles blade assist helpers', function (): void {
    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('composer require laravel/ai:^1.0 laravel/mcp:^1.0 -W')
        ->toContain('composer show laravel/ai')
        ->toContain('composer require aws/aws-sdk-php')
        ->toContain('php artisan migrate')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('{{ $assist')
        ->not->toContain('<details>');
});
