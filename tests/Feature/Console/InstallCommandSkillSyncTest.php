<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-skill-sync-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    file_put_contents($this->tempBasePath.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
        ],
        'packages-dev' => [],
    ]));

    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setCloud(false);
});

afterEach(function (): void {
    foreach (['first-failed-skill', 'second-failed-skill'] as $name) {
        $target = $this->tempBasePath.'/.claude/skills/'.$name;

        if (is_file($target.'/asset.txt')) {
            chmod($target.'/asset.txt', 0644);
        }

        if (is_dir($target)) {
            chmod($target, 0755);
        }
    }

    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

it('reports a failed skill sync instead of displaying success', function (): void {
    $skills = collect(['first-failed-skill', 'second-failed-skill'])
        ->mapWithKeys(function (string $name): array {
            $source = $this->tempBasePath.'/source-'.$name;
            $target = $this->tempBasePath.'/.claude/skills/'.$name;

            File::makeDirectory($source, 0755, true);
            File::put($source.'/SKILL.md', "---\nname: {$name}\ndescription: Permission reproduction\n---\n");
            File::put($source.'/asset.txt', 'new content');

            File::makeDirectory($target, 0755, true);
            File::put($target.'/SKILL.md', 'old content');
            File::put($target.'/asset.txt', 'old content');

            chmod($target.'/asset.txt', 0444);
            chmod($target, 0555);

            return [$name => new Skill(
                name: $name,
                package: 'boost',
                path: $source,
                description: 'Permission reproduction',
            )];
        });

    $composer = Mockery::mock(SkillComposer::class);
    $composer->shouldReceive('config')->andReturnSelf();
    $composer->shouldReceive('skills')->andReturn($skills);

    $this->app->instance(SkillComposer::class, $composer);

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Failed to sync skills: first-failed-skill, second-failed-skill')
        ->assertSuccessful();
})->skipOnWindows();
