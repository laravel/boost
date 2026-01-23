<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Console\UpdateCommand;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
});

it('it shows an error when boost.json does not exist', function (): void {
    $this->artisan('boost:update')
        ->expectsOutputToContain('Please set up Boost with [php artisan boost:install] first.')
        ->assertFailed();
});

it('it shows an error when boost.json contains invalid json', function (): void {
    file_put_contents(base_path('boost.json'), 'invalid json {{{');

    $this->artisan('boost:update')
        ->expectsOutputToContain('Please set up Boost with [php artisan boost:install] first.')
        ->assertFailed();
});

it('it shows an error when agents are empty', function (): void {
    $config = new Config;
    $config->setGuidelines(true);

    $this->artisan('boost:update')
        ->expectsOutputToContain('Please set up Boost with [php artisan boost:install] first.')
        ->assertFailed();
});

it('exits silently when no guidelines and no skills are configured', function (): void {
    $config = new Config;
    $config->setAgents(['claude-code']);
    $config->setGuidelines(false);
    $config->setSkills([]);

    $this->artisan('boost:update')
        ->doesntExpectOutputToContain('Boost guidelines and skills updated successfully.')
        ->assertSuccessful();
});

it('calls install command with a guidelines flag when guidelines are enabled', function (): void {
    $config = new Config;
    $config->setAgents(['claude-code']);
    $config->setGuidelines(true);
    $config->setSkills([]);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('callSilently')
        ->once()
        ->with(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => true,
            '--skills' => false,
        ])
        ->andReturn(0);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);

    $command->setLaravel($this->app);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0);
});

it('calls install command with skills flag when skills are configured', function (): void {
    $config = new Config;
    $config->setAgents(['claude-code']);
    $config->setGuidelines(false);
    $config->setSkills(['test-skill']);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('callSilently')
        ->once()
        ->with(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => false,
            '--skills' => true,
        ])
        ->andReturn(0);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);

    $command->setLaravel($this->app);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0);
});

it('calls install command with both flags when guidelines and skills are enabled', function (): void {
    $config = new Config;
    $config->setAgents(['claude-code']);
    $config->setGuidelines(true);
    $config->setSkills(['test-skill']);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('callSilently')
        ->once()
        ->with(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => true,
            '--skills' => true,
        ])
        ->andReturn(0);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);

    $command->setLaravel($this->app);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0);
});
