<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Console\AddGuidelineCommand;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\Herd;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

function makeTestAddGuidelineCommand(Terminal $terminal, Closure $onUpdate): AddGuidelineCommand
{
    return new class($terminal, $onUpdate) extends AddGuidelineCommand
    {
        public function __construct(Terminal $terminal, private readonly Closure $onUpdate)
        {
            parent::__construct($terminal);
        }

        protected function runBoostUpdate(): void
        {
            ($this->onUpdate)();
        }
    };
}

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-add-guideline-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    Http::preventStrayRequests();
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

function fakeAddGuidelineTree(array $tree, string $branch = 'main'): array
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

it('throws an exception for an invalid repository format', function (): void {
    $this->artisan('boost:add-guideline', ['repo' => 'invalid-format']);
})->throws(InvalidArgumentException::class, 'Invalid repository format');

it('lists guidelines by their normalized relative paths', function (): void {
    Http::fake(fakeAddGuidelineTree([
        ['path' => '.ai/guidelines/laravel/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ['path' => '.ai/guidelines/testing.md', 'type' => 'blob', 'sha' => 'bbb'],
        ['path' => 'README.md', 'type' => 'blob', 'sha' => 'ccc'],
    ]));

    $this->artisan('boost:add-guideline', ['repo' => 'owner/repo', '--list' => true])
        ->expectsOutputToContain('Found 2 available guidelines')
        ->expectsOutputToContain('laravel/core')
        ->assertSuccessful();

    Http::assertSentCount(2);
});

it('reports an empty repository without treating unrelated Markdown as guidelines', function (): void {
    Http::fake(fakeAddGuidelineTree([
        ['path' => 'README.md', 'type' => 'blob', 'sha' => 'aaa'],
        ['path' => 'docs/conventions.md', 'type' => 'blob', 'sha' => 'bbb'],
        ['path' => '.ai/guidelines/template.blade.php', 'type' => 'blob', 'sha' => 'ccc'],
    ]));

    $this->artisan('boost:add-guideline', ['repo' => 'owner/repo'])
        ->expectsOutputToContain('No Markdown guidelines were found')
        ->assertFailed();
});

it('reports failed GitHub tree responses', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo' => Http::response(['default_branch' => 'main']),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->artisan('boost:add-guideline', ['repo' => 'owner/repo'])
        ->expectsOutputToContain('Failed to fetch repository tree from GitHub: Not Found (HTTP 404)')
        ->assertFailed();
});

it('reports GitHub rate limits using the shared transport handling', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo' => Http::response(['default_branch' => 'main']),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['message' => 'API rate limit exceeded'],
            403,
            [
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) (time() + 3600),
            ],
        ),
    ]);

    $this->artisan('boost:add-guideline', ['repo' => 'owner/repo'])
        ->expectsOutputToContain('GitHub API rate limit exceeded')
        ->assertFailed();
});

it('installs all guidelines from an explicit repository subpath', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => 'shared/guidelines/backend/core.md', 'type' => 'blob', 'sha' => 'aaa'],
            ['path' => 'shared/guidelines/frontend/core.md', 'type' => 'blob', 'sha' => 'bbb'],
            ['path' => '.ai/guidelines/default.md', 'type' => 'blob', 'sha' => 'ccc'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/shared/guidelines/backend/core.md' => Http::response('# Backend Core'),
        'raw.githubusercontent.com/owner/repo/main/shared/guidelines/frontend/core.md' => Http::response('# Frontend Core'),
    ]);

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo/shared/guidelines',
        '--all' => true,
    ])
        ->expectsOutputToContain('Guidelines installed')
        ->assertSuccessful();

    expect($this->tempBasePath.'/.ai/guidelines/backend/core.md')->toBeFile()
        ->and($this->tempBasePath.'/.ai/guidelines/frontend/core.md')->toBeFile()
        ->and($this->tempBasePath.'/.ai/guidelines/default.md')->not->toBeFile();
});

it('installs only guidelines named by repeatable guideline options', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/backend/core.md', 'type' => 'blob', 'sha' => 'aaa'],
            ['path' => '.ai/guidelines/frontend/core.md', 'type' => 'blob', 'sha' => 'bbb'],
            ['path' => '.ai/guidelines/testing.md', 'type' => 'blob', 'sha' => 'ccc'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/backend/core.md' => Http::response('# Backend Core'),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/testing.md' => Http::response('# Testing'),
    ]);

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--guideline' => ['backend/core', 'testing'],
    ])->assertSuccessful();

    expect($this->tempBasePath.'/.ai/guidelines/backend/core.md')->toBeFile()
        ->and($this->tempBasePath.'/.ai/guidelines/testing.md')->toBeFile()
        ->and($this->tempBasePath.'/.ai/guidelines/frontend/core.md')->not->toBeFile();

    Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), 'frontend/core.md'));
});

it('prompts for a repository and an interactive guideline selection', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/first.md', 'type' => 'blob', 'sha' => 'aaa'],
            ['path' => '.ai/guidelines/second.md', 'type' => 'blob', 'sha' => 'bbb'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/first.md' => Http::response('# First'),
    ]);

    Prompt::fake([
        ...str_split('owner/repo'),
        Key::ENTER,
        Key::SPACE,
        Key::ENTER,
    ]);

    $updateCalls = 0;
    $command = makeTestAddGuidelineCommand(
        $this->app->make(Terminal::class),
        function () use (&$updateCalls): void {
            $updateCalls++;
        },
    );
    $command->setLaravel($this->app);

    $input = new ArrayInput([], $command->getDefinition());
    $input->setInteractive(true);

    $output = new OutputStyle($input, new BufferedOutput);
    $command->setInput($input);
    $command->setOutput($output);

    expect($command->handle())->toBe(0);

    expect($this->tempBasePath.'/.ai/guidelines/first.md')->toBeFile()
        ->and($this->tempBasePath.'/.ai/guidelines/second.md')->not->toBeFile()
        ->and($updateCalls)->toBe(1);
})->skipOnWindows();

it('skips existing guidelines in non-interactive mode', function (): void {
    File::makeDirectory($this->tempBasePath.'/.ai/guidelines/team', 0755, true);
    File::put($this->tempBasePath.'/.ai/guidelines/team/core.md', '# Existing');

    Http::fake(fakeAddGuidelineTree([
        ['path' => '.ai/guidelines/team/core.md', 'type' => 'blob', 'sha' => 'aaa'],
    ]));

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--all' => true,
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('Skipped 1 existing guideline')
        ->assertSuccessful();

    expect(file_get_contents($this->tempBasePath.'/.ai/guidelines/team/core.md'))->toBe('# Existing');

    Http::assertSentCount(2);
});

it('overwrites an existing guideline when forced', function (): void {
    File::makeDirectory($this->tempBasePath.'/.ai/guidelines/team', 0755, true);
    File::put($this->tempBasePath.'/.ai/guidelines/team/core.md', '# Existing');

    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/team/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/team/core.md' => Http::response('# Updated'),
    ]);

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--all' => true,
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents($this->tempBasePath.'/.ai/guidelines/team/core.md'))->toBe('# Updated');
});

it('overwrites an existing guideline when the interactive user confirms', function (): void {
    File::makeDirectory($this->tempBasePath.'/.ai/guidelines/team', 0755, true);
    File::put($this->tempBasePath.'/.ai/guidelines/team/core.md', '# Existing');

    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/team/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/team/core.md' => Http::response('# Confirmed'),
    ]);

    Prompt::fake([Key::ENTER]);

    $updateCalls = 0;
    $command = makeTestAddGuidelineCommand(
        $this->app->make(Terminal::class),
        function () use (&$updateCalls): void {
            $updateCalls++;
        },
    );
    $command->setLaravel($this->app);

    $input = new ArrayInput(['repo' => 'owner/repo', '--all' => true], $command->getDefinition());
    $input->setInteractive(true);

    $output = new OutputStyle($input, new BufferedOutput);
    $command->setInput($input);
    $command->setOutput($output);

    expect($command->handle())->toBe(0);

    expect(file_get_contents($this->tempBasePath.'/.ai/guidelines/team/core.md'))->toBe('# Confirmed')
        ->and($updateCalls)->toBe(1);
})->skipOnWindows();

it('does not prompt or install without a selector in non-interactive mode', function (): void {
    Http::fake(fakeAddGuidelineTree([
        ['path' => '.ai/guidelines/core.md', 'type' => 'blob', 'sha' => 'aaa'],
    ]));

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('No guidelines are selected')
        ->assertSuccessful();

    expect($this->tempBasePath.'/.ai/guidelines/core.md')->not->toBeFile();

    Http::assertSentCount(2);
});

it('reports individual download failures without installing a file', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/team/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/team/core.md' => Http::response('Server error', 500),
    ]);

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])
        ->expectsOutputToContain('Some guidelines failed to install')
        ->expectsOutputToContain('team/core')
        ->assertSuccessful();

    expect($this->tempBasePath.'/.ai/guidelines/team/core.md')->not->toBeFile();
});

it('runs boost update exactly once after multiple successful installations', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/one.md', 'type' => 'blob', 'sha' => 'aaa'],
            ['path' => '.ai/guidelines/two.md', 'type' => 'blob', 'sha' => 'bbb'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/one.md' => Http::response('# One'),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/two.md' => Http::response('# Two'),
    ]);

    $updateCalls = 0;
    $command = makeTestAddGuidelineCommand(
        $this->app->make(Terminal::class),
        function () use (&$updateCalls): void {
            $updateCalls++;
        },
    );
    $command->setLaravel($this->app);

    $input = new ArrayInput(['repo' => 'owner/repo', '--all' => true], $command->getDefinition());
    $input->setInteractive(false);

    expect($command->run($input, new BufferedOutput))->toBe(0)
        ->and($updateCalls)->toBe(1);
});

it('does not run boost update when no download succeeds', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/core.md' => Http::response('Server error', 500),
    ]);

    $updateCalls = 0;
    $command = makeTestAddGuidelineCommand(
        $this->app->make(Terminal::class),
        function () use (&$updateCalls): void {
            $updateCalls++;
        },
    );
    $command->setLaravel($this->app);

    $input = new ArrayInput(['repo' => 'owner/repo', '--all' => true], $command->getDefinition());
    $input->setInteractive(false);

    expect($command->run($input, new BufferedOutput))->toBe(0)
        ->and($updateCalls)->toBe(0);
});

it('composes a downloaded Markdown guideline as a matching Boost override', function (): void {
    Http::fake([
        ...fakeAddGuidelineTree([
            ['path' => '.ai/guidelines/laravel/core.md', 'type' => 'blob', 'sha' => 'aaa'],
        ]),
        'raw.githubusercontent.com/owner/repo/main/.ai/guidelines/laravel/core.md' => Http::response('# Downloaded Laravel Override'),
    ]);

    $this->artisan('boost:add-guideline', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])->assertSuccessful();

    config(['boost.rules.enabled' => false]);

    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]));

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('isInstalled')->andReturn(false);

    $composer = new GuidelineComposer($project, $herd);
    $laravelCore = $composer->guidelines()->get('laravel/core');

    expect($laravelCore)->not->toBeNull()
        ->and($laravelCore['path'])->toBe(realpath($this->tempBasePath.'/.ai/guidelines/laravel/core.md'))
        ->and($laravelCore['content'])->toContain('Downloaded Laravel Override')
        ->and(substr_count($composer->compose(), 'Downloaded Laravel Override'))->toBe(1);
});
