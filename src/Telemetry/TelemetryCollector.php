<?php

declare(strict_types=1);

namespace Laravel\Boost\Telemetry;

use Composer\InstalledVersions;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Throwable;

class TelemetryCollector
{
    use MakesHttpRequests;

    protected const MAX_TOOLS_PER_FLUSH = 20;

    public array $toolCounts = [];

    protected bool $enabled;

    protected string $url;

    protected string $sessionId;

    protected string $laravelVersion;

    public function __construct()
    {
        $this->enabled = config('boost.telemetry.enabled', false);
        if ($this->enabled) {
            $this->url = config('boost.telemetry.url', 'https://boost.laravel.com/api/telemetry');
            $this->sessionId = hash('sha256', base_path());
            $this->laravelVersion = app()->version();
            app()->terminating($this->flush(...));

            if (extension_loaded('pcntl')) {
                pcntl_async_signals(true);
                pcntl_signal(SIGINT, $this->flush(...));
                pcntl_signal(SIGTERM, $this->flush(...));
            }
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function record(string $toolName): void
    {
        if (! $this->enabled) {
            return;
        }

        $totalCount = array_sum($this->toolCounts);
        if ($totalCount >= self::MAX_TOOLS_PER_FLUSH) {
            $this->flush();
        }

        $this->toolCounts[$toolName] = ($this->toolCounts[$toolName] ?? 0) + 1;
    }

    public function flush(): void
    {
        if ($this->toolCounts === [] || ! $this->enabled) {
            return;
        }

        try {
            $this->client()
                ->timeout(5)
                ->post($this->url, ['data' => $this->buildPayload()]);
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
            'session_id' => $this->sessionId,
            'boost_version' => $version,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'laravel_version' => $this->laravelVersion,
            'tools' => $this->toolCounts,
            'timestamp' => date('c'),
        ]));
    }
}
