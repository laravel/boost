<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Guidelines\Remote\GitHubGuidelineProvider;
use Laravel\Boost\Guidelines\Remote\RemoteGuideline;
use Laravel\Boost\Support\GitHubRepository;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

function fakeGuidelineGitHubTree(array $tree, string $branch = 'main'): array
{
    return [
        'api.github.com/repos/owner/repo' => Http::response(['default_branch' => $branch]),
        "api.github.com/repos/owner/repo/git/trees/{$branch}?recursive=1" => Http::response([
            'sha' => 'abc123',
            'url' => 'https://api.github.com/repos/owner/repo/git/trees/abc123',
            'tree' => $tree,
            'truncated' => false,
        ]),
    ];
}

it('discovers only Markdown files beneath the default guideline root', function (): void {
    Http::fake(fakeGuidelineGitHubTree([
        ['path' => 'guidelines/laravel/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ['path' => 'guidelines/team/testing.md', 'type' => 'blob', 'sha' => 'bbb'],
        ['path' => 'guidelines/executable.blade.php', 'type' => 'blob', 'sha' => 'ccc'],
        ['path' => 'guidelines/payload.php', 'type' => 'blob', 'sha' => 'ddd'],
        ['path' => '.ai/guidelines/not-default.md', 'type' => 'blob', 'sha' => 'eee'],
        ['path' => 'README.md', 'type' => 'blob', 'sha' => 'fff'],
        ['path' => 'docs/conventions.md', 'type' => 'blob', 'sha' => 'ggg'],
    ]));

    $guidelines = (new GitHubGuidelineProvider(new GitHubRepository('owner', 'repo')))->discoverGuidelines();

    expect($guidelines->keys()->all())->toBe(['laravel/core', 'team/testing'])
        ->and($guidelines->get('laravel/core'))->toBeInstanceOf(RemoteGuideline::class)
        ->and($guidelines->get('laravel/core')->path)->toBe('guidelines/laravel/core.md')
        ->and($guidelines->get('laravel/core')->relativePath)->toBe('laravel/core.md');
});

it('uses an explicit repository subpath as the guideline root', function (): void {
    Http::fake(fakeGuidelineGitHubTree([
        ['path' => 'shared/guidelines/backend/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ['path' => '.ai/guidelines/default.md', 'type' => 'blob', 'sha' => 'bbb'],
        ['path' => 'shared/README.md', 'type' => 'blob', 'sha' => 'ccc'],
    ]));

    $guidelines = (new GitHubGuidelineProvider(
        new GitHubRepository('owner', 'repo', 'shared/guidelines')
    ))->discoverGuidelines();

    expect($guidelines->keys()->all())->toBe(['backend/core'])
        ->and($guidelines->first()->relativePath)->toBe('backend/core.md');
});

it('downloads a guideline while preserving its nested relative path and filename', function (): void {
    $targetPath = sys_get_temp_dir().'/boost-guideline-provider-'.uniqid();

    Http::fake([
        ...fakeGuidelineGitHubTree([
            ['path' => 'guidelines/laravel/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/guidelines/laravel/core.md' => Http::response('# Team Laravel Core'),
    ]);

    try {
        $provider = new GitHubGuidelineProvider(new GitHubRepository('owner', 'repo'));
        $guideline = $provider->discoverGuidelines()->sole();

        expect($provider->downloadGuideline($guideline, $targetPath))->toBeTrue()
            ->and($targetPath.'/laravel/core.md')->toBeFile()
            ->and(file_get_contents($targetPath.'/laravel/core.md'))->toBe('# Team Laravel Core');
    } finally {
        File::deleteDirectory($targetPath);
    }
});

it('does not request or download remote PHP and Blade files', function (): void {
    $targetPath = sys_get_temp_dir().'/boost-guideline-provider-'.uniqid();

    Http::fake([
        ...fakeGuidelineGitHubTree([
            ['path' => 'guidelines/safe.md', 'type' => 'blob', 'sha' => 'aaa'],
            ['path' => 'guidelines/template.blade.php', 'type' => 'blob', 'sha' => 'bbb'],
            ['path' => 'guidelines/payload.PHP', 'type' => 'blob', 'sha' => 'ccc'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/guidelines/safe.md' => Http::response('# Safe'),
    ]);

    try {
        $provider = new GitHubGuidelineProvider(new GitHubRepository('owner', 'repo'));
        $guideline = $provider->discoverGuidelines()->sole();

        expect($provider->downloadGuideline($guideline, $targetPath))->toBeTrue();

        Http::assertNotSent(fn ($request): bool => str_contains(strtolower((string) $request->url()), '.php'));
    } finally {
        File::deleteDirectory($targetPath);
    }
});

it('returns false without writing when a guideline download fails', function (): void {
    $targetPath = sys_get_temp_dir().'/boost-guideline-provider-'.uniqid();

    Http::fake([
        ...fakeGuidelineGitHubTree([
            ['path' => 'guidelines/team/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/guidelines/team/core.md' => Http::response('Server error', 500),
    ]);

    try {
        $provider = new GitHubGuidelineProvider(new GitHubRepository('owner', 'repo'));
        $guideline = $provider->discoverGuidelines()->sole();

        expect($provider->downloadGuideline($guideline, $targetPath))->toBeFalse()
            ->and($targetPath.'/team/core.md')->not->toBeFile();
    } finally {
        File::deleteDirectory($targetPath);
    }
});

it('reuses configured GitHub authentication', function (): void {
    config(['boost.github.token' => 'guideline-token']);

    Http::fake(fakeGuidelineGitHubTree([
        ['path' => 'guidelines/core.md', 'type' => 'blob', 'sha' => 'aaa'],
    ]));

    (new GitHubGuidelineProvider(new GitHubRepository('owner', 'repo')))->discoverGuidelines();

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer guideline-token'));
});
