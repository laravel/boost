<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-cloud-skill-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    file_put_contents($this->tempBasePath.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
        ],
        'packages-dev' => [],
    ]));

    config(['boost.agents.claude_code.mcp_config_path' => $this->tempBasePath.'/.mcp.json']);

    (new Config)->setAgents(['claude_code']);
});

afterEach(function (): void {
    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('does not download the cloud skill when the skills feature is not selected', function (): void {
    Http::fake();

    (new Config)->setCloud(true);

    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->assertSuccessful();

    Http::assertNothingSent();

    expect(is_dir($this->tempBasePath.'/.ai/skills'))->toBeFalse()
        ->and((new Config)->getCloud())->toBeTrue();
});

it('still downloads the cloud skill when the skills feature is selected', function (): void {
    Http::fake();

    (new Config)->setCloud(true);

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->assertSuccessful();

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'cloud-cli'));
});
