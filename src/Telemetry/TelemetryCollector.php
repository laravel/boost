<?php

declare(strict_types=1);

namespace Laravel\Boost\Telemetry;

use const PHP_OS_FAMILY;
use const PHP_VERSION;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelemetryCollector
{
    protected const MAX_TOOLS_PER_FLUSH = 20;

    public array $toolCounts = [];

    protected bool $shutdownRegistered = false;

    public function record(string $toolName): void
    {
        if (! config('boost.telemetry.enabled')) {
            return;
        }

        $totalCount = array_sum($this->toolCounts);
        if ($totalCount >= self::MAX_TOOLS_PER_FLUSH) {
            $this->flush();
        }

        if (! $this->shutdownRegistered) {
            if (extension_loaded('pcntl')) {
                pcntl_async_signals(true);
                pcntl_signal(SIGINT, $this->flush(...));
                pcntl_signal(SIGTERM, $this->flush(...));
            }

            register_shutdown_function([$this, 'flush']);

            app()->terminating($this->flush(...));

            $this->shutdownRegistered = true;
        }

        $this->toolCounts[$toolName] = ($this->toolCounts[$toolName] ?? 0) + 1;
    }

    public function flush(): void
    {
        if ($this->toolCounts === [] || ! config('boost.telemetry.enabled', true)) {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['User-Agent' => 'Laravel Boost Telemetry'])
                ->post(config('boost.telemetry.url'), ['data' => $this->buildPayload()]);
        } catch (Throwable) {
            //
        } finally {
            $this->toolCounts = [];
        }

    }

    protected function buildPayload(): string
    {
        $version = InstalledVersions::getVersion('laravel/boost');

        return base64_encode(json_encode([
            'session_id' => hash('sha256', base_path()),
            'boost_version' => $version,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'laravel_version' => app()->version(),
            'tools' => $this->toolCounts,
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
