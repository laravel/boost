<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-failure-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    $this->mcpConfigPath = $this->tempBasePath.'/cursor-mcp.json';
    File::put($this->mcpConfigPath, 'invalid json {{{');

    config([
        'boost.agents.cursor.mcp_config_path' => $this->mcpConfigPath,
        'boost.agents.factory.mcp_config_path' => $this->tempBasePath.'/factory-mcp.json',
    ]);

    (new Config)->setAgents(['cursor']);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('returns failure when an MCP configuration cannot be written', function (): void {
    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Failed to install Boost MCP: could not write configuration')
        ->doesntExpectOutputToContain('Enjoy the boost')
        ->assertFailed();
});

it('still installs remaining agents after one agent fails', function (): void {
    (new Config)->setAgents(['cursor', 'factory']);

    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->assertFailed();

    expect(File::get($this->mcpConfigPath))->toBe('invalid json {{{')
        ->and(json_decode(File::get($this->tempBasePath.'/factory-mcp.json'), true))
        ->toHaveKey('mcpServers.laravel-boost');
});

it('does not retain a failure between invocations of the same command instance', function (): void {
    $command = app(InstallCommand::class);
    $command->setLaravel($this->app);

    $input = new ArrayInput(['--mcp' => true], $command->getDefinition());
    $input->setInteractive(false);

    expect($command->run($input, new BufferedOutput))->toBe(1);

    File::put($this->mcpConfigPath, '{}');

    expect($command->run($input, new BufferedOutput))->toBe(0)
        ->and(json_decode(File::get($this->mcpConfigPath), true))
        ->toHaveKey('mcpServers.laravel-boost');
});
