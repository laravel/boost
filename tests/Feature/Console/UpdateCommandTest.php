<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Console\UpdateCommand;
use Laravel\Boost\Install\Skill;
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

it('defaults to non-sail when config is missing', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'guidelines' => true,
    ]));

    $config = new Config;

    // When sail config is missing, it defaults to false
    expect($config->getSail())->toBeFalse();
});

it('skips checking for new skills in non-interactive mode', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(false);
    $command->shouldNotReceive('resolveAvailableSkills');
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

it('does not prompt when all available skills are already installed', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect(['existing-skill' => $existingSkill]));
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

it('updates config when user selects new skills from prompt', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');
    $newSkill = new Skill('new-skill', 'boost', '/path', 'New Skill');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    Prompt::fake([Key::SPACE, Key::ENTER]);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
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
        ->and($config->getSkills())->toContain('existing-skill', 'new-skill')
        ->and($config->getDismissedSkills())->not->toContain('new-skill');
})->skipOnWindows();

it('does not update skills skills config when user skips selecting new skills', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');
    $newSkill = new Skill('new-skill', 'boost', '/path', 'New Skill');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    Prompt::fake([Key::ENTER]);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
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
        ->and($config->getSkills())->toBe(['existing-skill'])
        ->and($config->getDismissedSkills())->toBe(['new-skill']);
})->skipOnWindows();

it('persists dismissed skills to config when user skips the prompt', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');
    $newSkill = new Skill('new-skill', 'boost', '/path', 'New Skill');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
    $command->shouldReceive('callSilently')->andReturn(0);
    $command->setLaravel($this->app);

    $input = new ArrayInput([]);
    $output = new OutputStyle($input, new BufferedOutput);
    $command->setOutput($output);

    Prompt::fake([Key::ENTER]);

    $command->handle($config);

    // Dismissed, so the next call should not prompt
    $command2 = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command2->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command2->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
    $command2->shouldNotReceive('multiselect');
    $command2->shouldReceive('callSilently')->andReturn(0);
    $command2->setLaravel($this->app);

    $input2 = new ArrayInput([]);
    $output2 = new OutputStyle($input2, new BufferedOutput);
    $command2->setOutput($output2);

    expect($command2->handle($config))->toBe(0)
        ->and($config->getDismissedSkills())->toContain('new-skill');
})->skipOnWindows();

it('does not prompt for dismissed skills on subsequent runs', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');
    $newSkill = new Skill('new-skill', 'boost', '/path', 'New Skill');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);
    $config->setDismissedSkills(['new-skill']);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
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
        ->and($config->getSkills())->toBe(['existing-skill'])
        ->and($config->getDismissedSkills())->toBe(['new-skill']);
});

it('passes updated skills to install command after selection', function (): void {
    $existingSkill = new Skill('existing-skill', 'boost', '/path', 'Existing');
    $newSkill = new Skill('new-skill', 'boost', '/path', 'New Skill');

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setSkills(['existing-skill']);

    Prompt::fake([Key::SPACE, Key::ENTER]);

    $command = Mockery::mock(UpdateCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('isInteractiveMode')->once()->andReturn(true);
    $command->shouldReceive('resolveAvailableSkills')
        ->once()
        ->andReturn(collect([
            'existing-skill' => $existingSkill,
            'new-skill' => $newSkill,
        ]));
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
        ->and($config->getSkills())->toContain('existing-skill', 'new-skill')
        ->and($config->getDismissedSkills())->not->toContain('new-skill');
})->skipOnWindows();
