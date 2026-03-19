<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Console\UpdateCommand;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    (new Config)->flush();

    if (! file_exists(base_path('.ai/guidelines'))) {
        mkdir(base_path('.ai/guidelines'), 0755, true);
    }
});

afterEach(function (): void {
    (new Config)->flush();

    if (file_exists(base_path('CLAUDE.md'))) {
        unlink(base_path('CLAUDE.md'));
    }

    if (is_dir(base_path('.ai/skills'))) {
        rmdir(base_path('.ai/skills'));
    }
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
    $config->setAgents(['claude_code']);
    $config->setGuidelines(false);
    $config->setSkills([]);

    $this->artisan('boost:update')
        ->doesntExpectOutputToContain('Boost guidelines and skills updated successfully.')
        ->assertSuccessful();
});

it('calls install command with a guidelines flag when guidelines are enabled', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSkills([]);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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
    $config->setAgents(['claude_code']);
    $config->setGuidelines(false);
    $config->setSkills(['test-skill']);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSkills(['test-skill']);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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

it('preserves sail configuration when updating guidelines', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSail(true);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
    $command->shouldReceive('callSilently')
        ->once()
        ->with(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => true,
            '--skills' => false,
        ])
        ->andReturnUsing(fn (): int => 0);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);

    $command->setLaravel($this->app);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0)
        ->and($config->getSail())->toBeTrue();
});

it('preserves non-sail configuration when updating guidelines', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSail(false);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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

    expect($command->handle($config))->toBe(0)
        ->and($config->getSail())->toBeFalse();
});

it('preserves sail configuration when updating skills', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['commit']);
    $config->setSail(true);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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

    expect($command->handle($config))->toBe(0)
        ->and($config->getSail())->toBeTrue();
});

it('calls install command with skills flag when .ai/skills directory exists but skills are not in config', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(false);

    mkdir(base_path('.ai/skills'), 0755, true);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
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

it('defaults to non-sail when config is missing', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'guidelines' => true,
    ]));

    $config = new Config;

    // When sail config is missing, it defaults to false
    expect($config->getSail())->toBeFalse();
});

it('does not run discovery when --discover flag is not set', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
    $command->shouldNotReceive('discoverNewContent');
    $command->shouldReceive('callSilently')
        ->once()
        ->with(InstallCommand::class, [
            '--no-interaction' => true,
            '--guidelines' => false,
            '--skills' => true,
        ])
        ->andReturn(0);
    $command->setLaravel($this->app);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0)
        ->and($config->getSkills())->toBe(['existing-skill']);
});

it('does not change config when no new packages are found with --discover', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSkills(['existing-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('discover')->andReturn(true);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
    $command->shouldReceive('resolveNewPackages')->andReturn(collect());
    $command->shouldReceive('callSilently')->once()->andReturn(0);
    $command->setLaravel($this->app);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0)
        ->and($config->getSkills())->toBe(['existing-skill'])
        ->and($config->getPackages())->toBe([]);
});

it('adds selected new packages to config when using --discover', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setPackages([]);

    $newPackage = new ThirdPartyPackage('vendor/awesome-pkg', true, false);

    Prompt::fake([Key::SPACE, Key::ENTER]);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('option')->with('discover')->andReturn(true);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(false);
    $command->shouldReceive('resolveNewPackages')
        ->andReturn(collect(['vendor/awesome-pkg' => $newPackage]));
    $command->shouldReceive('callSilently')->andReturn(0);
    $command->setLaravel($this->app);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);
    $command->setOutput($output);

    expect($command->handle($config))->toBe(0)
        ->and($config->getPackages())->toContain('vendor/awesome-pkg');
})->skipOnWindows();

it('skips skills when --ignore-skills flag is set even if skills are configured', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);
    $config->setSkills(['test-skill']);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(true);
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

it('skips skills when --ignore-skills flag is set even if .ai/skills directory exists', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(true);

    mkdir(base_path('.ai/skills'), 0755, true);

    $command = Mockery::mock(UpdateCommand::class)->makePartial();
    $command->shouldReceive('option')->with('discover')->andReturn(false);
    $command->shouldReceive('option')->with('ignore-skills')->andReturn(true);
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

it('exits silently when --ignore-skills flag is set and no guidelines are configured', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setGuidelines(false);
    $config->setSkills(['test-skill']);

    $this->artisan('boost:update', ['--ignore-skills' => true])
        ->doesntExpectOutputToContain('Boost guidelines and skills updated successfully.')
        ->assertSuccessful();
});
