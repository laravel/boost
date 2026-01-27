<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Laravel\Boost\Exceptions\GitHubSkillProviderException;
use Laravel\Boost\Skills\Remote\GitHubRepository;
use Laravel\Boost\Skills\Remote\GitHubSkillProvider;
use Laravel\Boost\Skills\Remote\RemoteSkill;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('discovers skills from repository directories', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
            ['name' => 'skill-two', 'path' => 'skill-two', 'type' => 'dir'],
            ['name' => 'README.md', 'path' => 'README.md', 'type' => 'file'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL'),
        'raw.githubusercontent.com/owner/repo/main/skill-two/SKILL.md' => Http::response('# SKILL'),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(2)
        ->and($skills->has('skill-one'))->toBeTrue()
        ->and($skills->has('skill-two'))->toBeTrue()
        ->and($skills->get('skill-one'))->toBeInstanceOf(RemoteSkill::class)
        ->and($skills->get('skill-one')->name)->toBe('skill-one')
        ->and($skills->get('skill-two')->name)->toBe('skill-two');
});

it('skips directories without SKILL.md', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'valid-skill', 'path' => 'valid-skill', 'type' => 'dir'],
            ['name' => 'no-skill-file', 'path' => 'no-skill-file', 'type' => 'dir'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/valid-skill/SKILL.md' => Http::response('# SKILL'),
        'raw.githubusercontent.com/owner/repo/main/no-skill-file/SKILL.md' => Http::response(null, 404),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('valid-skill'))->toBeTrue()
        ->and($skills->has('no-skill-file'))->toBeFalse();
});

it('throws exception when api fails', function (): void {
    Http::fake([
        'api.github.com/*' => Http::response('{"message":"Not Found"}', 404),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->discoverSkills();
})->throws(GitHubSkillProviderException::class, 'Not Found');

it('throws exception with parsed message when rate limited', function (): void {
    Http::fake([
        'api.github.com/*' => Http::response('{"message":"API rate limit exceeded"}', 403),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->discoverSkills();
})->throws(GitHubSkillProviderException::class, 'API rate limit exceeded');

it('throws exception with raw body when response is not json', function (): void {
    Http::fake([
        'api.github.com/*' => Http::response('Service Unavailable', 503),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->discoverSkills();
})->throws(GitHubSkillProviderException::class, 'Service Unavailable');

it('downloads skill files to target directory', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
            ['name' => 'README.md', 'path' => 'skill-one/README.md', 'type' => 'file'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL Content'),
        'raw.githubusercontent.com/owner/repo/main/skill-one/README.md' => Http::response('# README Content'),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $result = $fetcher->downloadSkill($skill, $targetDir);

    expect($result)->toBeTrue()
        ->and($targetDir.'/SKILL.md')->toBeFile()
        ->and($targetDir.'/README.md')->toBeFile()
        ->and(file_get_contents($targetDir.'/SKILL.md'))->toBe('# SKILL Content')
        ->and(file_get_contents($targetDir.'/README.md'))->toBe('# README Content');

    array_map(unlink(...), glob($targetDir.'/*'));
    rmdir($targetDir);
});

it('downloads nested directory structure', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
            ['name' => 'examples', 'path' => 'skill-one/examples', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one/examples' => Http::response([
            ['name' => 'example.md', 'path' => 'skill-one/examples/example.md', 'type' => 'file'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL'),
        'raw.githubusercontent.com/owner/repo/main/skill-one/examples/example.md' => Http::response('# Example'),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $result = $fetcher->downloadSkill($skill, $targetDir);

    expect($result)->toBeTrue()
        ->and($targetDir.'/SKILL.md')->toBeFile()
        ->and($targetDir.'/examples/example.md')->toBeFile();

    @unlink($targetDir.'/examples/example.md');
    @rmdir($targetDir.'/examples');
    @unlink($targetDir.'/SKILL.md');
    @rmdir($targetDir);
});

it('throws exception when download fails', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response('{"message":"Not Found"}', 404),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->downloadSkill($skill, $targetDir);

    @rmdir($targetDir);
})->throws(GitHubSkillProviderException::class, 'Not Found');

it('handles empty repository', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/contents/' => Http::response([]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toBeEmpty();
});

it('ignores files at root level', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'README.md', 'path' => 'README.md', 'type' => 'file'],
            ['name' => 'LICENSE', 'path' => 'LICENSE', 'type' => 'file'],
            ['name' => '.gitignore', 'path' => '.gitignore', 'type' => 'file'],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toBeEmpty();
});
