<?php

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Boost\Telemetry\TelemetryCollector;

beforeEach(function (): void {
    app()->forgetInstance(TelemetryCollector::class);

    config(['boost.telemetry.enabled' => true]);
    Http::fake(['*' => Http::response(['status' => 'ok'])]);
    $this->collector = app(TelemetryCollector::class);
    $this->collector->toolData = [];
    $this->collector->resourceData = [];
    $this->collector->promptData = [];
});

it('records tool invocations', function (): void {
    $this->collector->recordTool(DatabaseQuery::class, 100);
    $this->collector->recordTool(DatabaseQuery::class, 200);
    $this->collector->recordTool(Tinker::class, 150);

    expect($this->collector->toolData)->toBe([
        DatabaseQuery::class => [
            ['tokens' => 130], // 100 * 1.3
            ['tokens' => 260], // 200 * 1.3
        ],
        Tinker::class => [
            ['tokens' => 195], // 150 * 1.3
        ],
    ]);
});

it('records resource invocations', function (): void {
    $this->collector->recordResource('file://instructions/application-info.md');
    $this->collector->recordResource('file://instructions/application-info.md');
    $this->collector->recordResource('file://instructions/laravel/framework.md');

    expect($this->collector->resourceData)->toBe([
        'file://instructions/application-info.md' => 2,
        'file://instructions/laravel/framework.md' => 1,
    ]);
});

it('records prompt invocations', function (): void {
    $this->collector->recordPrompt('laravel/framework');
    $this->collector->recordPrompt('laravel/framework');
    $this->collector->recordPrompt('laravel/pint');

    expect($this->collector->promptData)->toBe([
        'laravel/framework' => 2,
        'laravel/pint' => 1,
    ]);
});

it('does not record when disabled via config', function (): void {
    config(['boost.telemetry.enabled' => false]);

    $collector = new TelemetryCollector;
    $collector->recordTool(DatabaseQuery::class, 100);

    expect($collector->toolData)->toBe([]);
});

it('flush sends data and clears counts', function (): void {
    $this->collector->recordTool(Tinker::class, 150);
    $this->collector->recordResource('file://instructions/application-info.md');
    $this->collector->recordPrompt('laravel/framework');
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($request->url())->toBe(config('boost.telemetry.url'))
        ->and($payload['tools'][Tinker::class]['1'])->toBe(['tokens' => 195]) // 150 * 1.3
        ->and($payload['resources']['file://instructions/application-info.md'])->toBe(1)
        ->and($payload['prompts']['laravel/framework'])->toBe(1)
        ->and($this->collector->toolData)->toBe([])
        ->and($this->collector->resourceData)->toBe([])
        ->and($this->collector->promptData)->toBe([]);
});

it('flush does nothing when toolData is empty', function (): void {
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(0);
});

it('flush does nothing when telemetry is disabled', function (): void {
    config(['boost.telemetry.enabled' => false]);

    $collector = new TelemetryCollector;
    $collector->toolData = ['SomeTool' => [['tokens' => 100]]];
    $collector->flush();

    expect(Http::recorded())->toHaveCount(0);
});

it('flush fails silently on network error', function (): void {
    Http::fake(['*' => Http::response(null, 500)]);

    $this->collector->recordTool(Tinker::class, 100);
    $this->collector->flush();

    expect($this->collector->toolData)->toBe([]);
});

it('flush fails silently on connection timeout', function (): void {
    Http::fake(function (): void {
        throw new \Exception('Connection timeout');
    });

    $this->collector->recordTool(Tinker::class, 100);
    $this->collector->flush();

    expect($this->collector->toolData)->toBe([]);
});

it('includes buildPayload as the correct structure', function (): void {
    $this->collector->recordTool(Tinker::class, 100);
    $this->collector->recordResource('file://instructions/application-info.md');
    $this->collector->recordPrompt('laravel/framework');
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload)->toHaveKeys([
        'session_id',
        'boost_version',
        'php_version',
        'os',
        'laravel_version',
        'session_start',
        'session_end',
        'tools',
        'resources',
        'prompts',
        'timestamp',
    ])
        ->and($payload['php_version'])->toBe(PHP_VERSION)
        ->and($payload['os'])->toBe(PHP_OS_FAMILY)
        ->and($payload['tools'])->toBeArray()
        ->and($payload['tools'][Tinker::class]['1']['tokens'])->toBe(130) // 100 * 1.3
        ->and($payload['resources']['file://instructions/application-info.md'])->toBe(1)
        ->and($payload['prompts']['laravel/framework'])->toBe(1)
        ->and(strtotime((string) $payload['session_start']))->not->toBeFalse()
        ->and(strtotime((string) $payload['session_end']))->not->toBeFalse();
});

it('sends session_id as a consistent hash of base_path', function (): void {
    $expectedSessionId = hash('sha256', base_path());

    $this->collector->recordTool(Tinker::class, 100);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload['session_id'])->toBe($expectedSessionId);
});

it('records tool response sizes and resets after flush', function (): void {
    $this->collector->recordTool(Tinker::class, 128);
    $this->collector->recordTool(Tinker::class, 256);
    $this->collector->recordResource('file://instructions/laravel/framework.md');
    $this->collector->recordPrompt('laravel/framework');

    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload['tools'][Tinker::class]['1']['tokens'])->toBe(166) // 128 * 1.3
        ->and($payload['tools'][Tinker::class]['2']['tokens'])->toBe(333) // 256 * 1.3
        ->and($payload['resources']['file://instructions/laravel/framework.md'])->toBe(1)
        ->and($payload['prompts']['laravel/framework'])->toBe(1)
        ->and($this->collector->toolData)->toBe([])
        ->and($this->collector->resourceData)->toBe([])
        ->and($this->collector->promptData)->toBe([]);
});

it('uses boost_version as InstalledVersions', function (): void {
    $expectedVersion = InstalledVersions::getVersion('laravel/boost');

    $this->collector->recordTool(Tinker::class, 100);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload['boost_version'])->toBe($expectedVersion);
});

it('resets resource and prompts data after a flush', function (): void {
    $this->collector->recordResource('file://instructions/application-info.md');
    $this->collector->recordPrompt('laravel/framework');
    $this->collector->flush();

    expect($this->collector->resourceData)->toBe([])
        ->and($this->collector->promptData)->toBe([]);
});
