<?php

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Boost\Telemetry\TelemetryCollector;

beforeEach(function (): void {
    $this->collector = app(TelemetryCollector::class);
    $this->collector->toolCounts = [];
});

it('records tool invocations', function (): void {
    config(['boost.telemetry.enabled' => true]);

    $this->collector->record(DatabaseQuery::class);
    $this->collector->record(DatabaseQuery::class);
    $this->collector->record(Tinker::class);

    expect($this->collector->toolCounts)->toBe([
        DatabaseQuery::class => 2,
        Tinker::class => 1,
    ]);
});

it('does not record when disabled via config', function (): void {
    config(['boost.telemetry.enabled' => false]);

    $this->collector->record(DatabaseQuery::class);

    expect($this->collector->toolCounts)->toBe([]);
});

it('auto-flushes when reaching MAX_TOOLS_PER_FLUSH', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    for ($i = 0; $i < 20; $i++) {
        $this->collector->record(Tinker::class);
    }

    expect($this->collector->toolCounts)->toHaveCount(1)
        ->and($this->collector->toolCounts[Tinker::class])->toBe(20);

    $this->collector->record(Tinker::class);

    expect(Http::recorded())->toHaveCount(1)
        ->and($this->collector->toolCounts)->toHaveCount(1)
        ->and($this->collector->toolCounts[Tinker::class])->toBe(1);
});

it('does not auto-flush below MAX_TOOLS_PER_FLUSH', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    for ($i = 0; $i < 19; $i++) {
        $this->collector->record(Tinker::class);
    }

    expect(Http::recorded())->toHaveCount(0)
        ->and($this->collector->toolCounts)->toHaveCount(1)
        ->and($this->collector->toolCounts[Tinker::class])->toBe(19);
});

it('flush sends data and clears counts', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($request->url())->toBe(config('boost.telemetry.url'))
        ->and($payload['tools'][Tinker::class])->toBe(1)
        ->and($this->collector->toolCounts)->toBe([]);
});

it('flush does nothing when toolCounts is empty', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(0);
});

it('flush does nothing when telemetry is disabled', function (): void {
    config(['boost.telemetry.enabled' => false]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->collector->toolCounts = ['SomeTool' => 1];
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(0);
});

it('flush fails silently on network error', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect($this->collector->toolCounts)->toBe([]);
});

it('flush fails silently on connection timeout', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake(function (): void {
        throw new \Exception('Connection timeout');
    });

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect($this->collector->toolCounts)->toBe([]);
});

it('includes buildPayload as the correct structure', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload)->toHaveKeys(['session_id', 'boost_version', 'php_version', 'os', 'laravel_version', 'tools', 'timestamp'])
        ->and($payload['php_version'])->toBe(PHP_VERSION)
        ->and($payload['os'])->toBe(PHP_OS_FAMILY)
        ->and($payload['tools'])->toBeArray();
});

it('sends session_id as a consistent hash of base_path', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $expectedSessionId = hash('sha256', base_path());

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload['session_id'])->toBe($expectedSessionId);
});

it('uses boost_version as InstalledVersions', function (): void {
    config(['boost.telemetry.enabled' => true]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $expectedVersion = InstalledVersions::getVersion('laravel/boost');

    $this->collector->record(Tinker::class);
    $this->collector->flush();

    expect(Http::recorded())->toHaveCount(1);

    $request = Http::recorded()[0][0];
    $payload = json_decode(base64_decode((string) $request['data'], true), true);

    expect($payload['boost_version'])->toBe($expectedVersion);
});
