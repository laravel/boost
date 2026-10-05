<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use JMac\Testing\Double;
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

    (new Config)->setAgents(['claude_code']);
});

afterEach(function (): void {
    $skillsPath = $this->tempBasePath.'/.claude/skills';

    if (is_dir($skillsPath)) {
        foreach (File::directories($skillsPath) as $directory) {
            foreach (File::files($directory) as $file) {
                chmod($file->getPathname(), 0644);
            }

            chmod($directory, 0755);
        }
    }

    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

/**
 * @param  array<int, string>  $names
 * @return Collection<string, Skill>
 */
function unwritableSkills(string $basePath, array $names): Collection
{
    return collect($names)->mapWithKeys(function (string $name) use ($basePath): array {
        $source = $basePath.'/source-'.$name;
        $target = $basePath.'/.claude/skills/'.$name;

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
}

/**
 * @param  Collection<string, Skill>  $skills
 */
function mockSkillComposer(Collection $skills): void
{
    $composer = Double::for(SkillComposer::class);
    $composer->allows('config')->returns($composer);
    $composer->allows('skills')->returns($skills);

    app()->instance(SkillComposer::class, $composer);
}

it('reports a failed skill sync instead of displaying success', function (): void {
    mockSkillComposer(unwritableSkills($this->tempBasePath, ['first-failed-skill', 'second-failed-skill']));

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Failed to sync skills: first-failed-skill, second-failed-skill')
        ->assertSuccessful();
})->skipOnWindows();

it('does not track a skill that failed to sync', function (): void {
    mockSkillComposer(unwritableSkills($this->tempBasePath, ['failed-skill']));

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect((new Config)->getSkills())->not->toContain('failed-skill');
})->skipOnWindows();

it('reports a stale skill that could not be removed', function (): void {
    $stale = $this->tempBasePath.'/.claude/skills/stale-skill';

    File::makeDirectory($stale, 0755, true);
    File::put($stale.'/SKILL.md', 'stale content');
    chmod($stale, 0555);

    (new Config)->setSkills(['stale-skill']);

    mockSkillComposer(collect());

    $this->artisan('boost:install', ['--skills' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Failed to sync skills: stale-skill')
        ->assertSuccessful();

    expect($stale)->toBeDirectory();
})->skipOnWindows();
