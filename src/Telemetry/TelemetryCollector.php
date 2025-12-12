<?php

declare(strict_types=1);

namespace Laravel\Boost\Telemetry;

use Composer\InstalledVersions;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Throwable;

class TelemetryCollector
{
    use MakesHttpRequests;

    public array $toolData = [];

    protected bool $enabled;

    protected string $url;

    protected string $sessionId;

    protected string $laravelVersion;

    protected float $sessionStartTime;

    public function __construct()
    {
        $this->sessionStartTime = microtime(true);
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

    public function record(string $toolName, int $wordCount): void
    {
        if (! $this->enabled) {
            return;
        }

        if (! isset($this->toolData[$toolName])) {
            $this->toolData[$toolName] = [];
        }

        $tokens = $this->calculateTokens($wordCount);
        $this->toolData[$toolName][] = ['tokens' => $tokens];
    }

    protected function calculateTokens(int $wordCount): int
    {
        return (int) round($wordCount * 1.3);
    }

    public function flush(): void
    {
        if ($this->toolData === [] || ! $this->enabled) {
            return;
        }

        try {
            $this->client()
                ->timeout(5)
                ->post($this->url, ['data' => $this->buildPayload()]);
        } catch (Throwable) {
            //
        } finally {
            $this->toolData = [];
            $this->sessionStartTime = microtime(true);
        }
    }

    protected function buildPayload(): string
    {
        $version = InstalledVersions::getVersion('laravel/boost');
        $sessionEndTime = microtime(true);

        return base64_encode(json_encode([
            'session_id' => $this->sessionId,
            'boost_version' => $version,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'laravel_version' => $this->laravelVersion,
            'session_start' => date('c', (int) $this->sessionStartTime),
            'session_end' => date('c', (int) $sessionEndTime),
            'tools' => $this->formatToolsData(),
            'timestamp' => date('c'),
        ]));
    }

    protected function formatToolsData(): array
    {
        $formatted = [];

        foreach ($this->toolData as $toolName => $invocations) {
            $formatted[$toolName] = [];
            foreach ($invocations as $index => $invocation) {
                $formatted[$toolName][(string) ($index + 1)] = $invocation;
            }
        }

        return $formatted;
    }
}
