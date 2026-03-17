---
name: laravel-nightwatch
description: "Use this skill when setting up, configuring, or troubleshooting Laravel Nightwatch monitoring. Trigger whenever the user asks about application monitoring, performance tracking, error tracking, telemetry, or the Nightwatch agent process. Covers: installation, agent setup, environment configuration, sampling rates, logging integration, Slack/Linear alerts, MCP server, programmatic API, and disabling in tests. Do not use for general application debugging, log file reading, or non-Nightwatch monitoring tools."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Nightwatch

## Documentation

Use `search-docs` for detailed Nightwatch documentation and troubleshooting.

## Installation

Install the package and set your token:

@boostsnippet("Install Nightwatch", "bash")
composer require laravel/nightwatch
@endboostsnippet

Add to `.env`:

@boostsnippet("Nightwatch Environment Token", "env")
NIGHTWATCH_TOKEN=your-nightwatch-token
@endboostsnippet

Start the agent process:

{{ $assist->artisanCommand('nightwatch:agent') }}

## Agent Process Setup (Production)

The Nightwatch agent must run as a long-lived process alongside your application.

### Supervisor Configuration

@boostsnippet("Supervisor Config for Nightwatch Agent", "ini")
[program:nightwatch-agent]
process_name=%(program_name)s
command=php /home/forge/app.com/artisan nightwatch:agent
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
redirect_stderr=true
stdout_logfile=/home/forge/app.com/storage/logs/nightwatch-agent.log
stopwaitsecs=3600
@endboostsnippet

### Docker Agent Image

Use the official Docker agent image as a sidecar container instead of the artisan command:

@boostsnippet("Docker Nightwatch Agent", "yaml")
services:
  nightwatch-agent:
    image: ghcr.io/laravel/nightwatch-agent:latest
    environment:
      - NIGHTWATCH_TOKEN=${NIGHTWATCH_TOKEN}
      - NIGHTWATCH_SERVER=${NIGHTWATCH_SERVER}
@endboostsnippet

### Multi-App Setup

Run multiple agents on separate ports using `--listen-on`:

@boostsnippet("Multi-App Agent Ports", "bash")
php artisan nightwatch:agent --listen-on=9100
php artisan nightwatch:agent --listen-on=9101
@endboostsnippet

### Octane Support

Nightwatch works with Laravel Octane. No additional configuration is needed.

## Environment Variables

### Core Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `NIGHTWATCH_ENABLED` | `true` | Enable or disable Nightwatch |
| `NIGHTWATCH_TOKEN` | — | **Required.** Your Nightwatch authentication token |
| `NIGHTWATCH_SERVER` | — | Nightwatch server hostname |
| `NIGHTWATCH_DEPLOY` | Auto-detected commit hash | Deployment identifier |

### Data Capture

| Variable | Default | Description |
|----------|---------|-------------|
| `NIGHTWATCH_CAPTURE_REQUEST_PAYLOAD` | `false` | Capture request body payloads |
| `NIGHTWATCH_CAPTURE_EXCEPTION_SOURCE_CODE` | `true` | Include source code snippets with exceptions |

### Sampling Rates

Control what percentage of events are recorded (0.0 to 1.0):

| Variable | Description |
|----------|-------------|
| `NIGHTWATCH_REQUEST_SAMPLE_RATE` | HTTP request sampling rate |
| `NIGHTWATCH_COMMAND_SAMPLE_RATE` | Artisan command sampling rate |
| `NIGHTWATCH_EXCEPTION_SAMPLE_RATE` | Exception sampling rate |
| `NIGHTWATCH_SCHEDULED_TASK_SAMPLE_RATE` | Scheduled task sampling rate |

### Filtering Toggles

Disable specific event types:

| Variable | Description |
|----------|-------------|
| `NIGHTWATCH_IGNORE_CACHE_EVENTS` | Ignore cache events |
| `NIGHTWATCH_IGNORE_MAIL` | Ignore mail events |
| `NIGHTWATCH_IGNORE_NOTIFICATIONS` | Ignore notification events |
| `NIGHTWATCH_IGNORE_OUTGOING_REQUESTS` | Ignore outgoing HTTP requests |
| `NIGHTWATCH_IGNORE_QUERIES` | Ignore database query events |

### Logging and Redaction

| Variable | Default | Description |
|----------|---------|-------------|
| `NIGHTWATCH_LOG_LEVEL` | `debug` | Minimum log level to capture |
| `NIGHTWATCH_REDACT_PAYLOAD_FIELDS` | — | Comma-separated fields to redact from payloads |
| `NIGHTWATCH_REDACT_HEADERS` | — | Comma-separated headers to redact |

## Logging Integration

Configure the stack channel to include Nightwatch:

@boostsnippet("Logging Configuration (.env)", "env")
LOG_CHANNEL=stack
LOG_STACK=single,nightwatch
@endboostsnippet

Nightwatch provides a `NightwatchHandler` for Monolog, which is automatically registered when using the `nightwatch` log channel.

## Programmatic API

### Ignoring Events

Suppress monitoring within a specific scope:

@boostsnippet("Nightwatch Ignore Example", "php")
use Laravel\Nightwatch\Nightwatch;

Nightwatch::ignore(function () {
    // Events within this callback are not recorded
    DB::table('health_checks')->get();
});
@endboostsnippet

### Pause and Resume

Manually control monitoring:

@boostsnippet("Nightwatch Pause and Resume", "php")
use Laravel\Nightwatch\Nightwatch;

Nightwatch::pause();

// Events here are not recorded
DB::table('internal_metrics')->insert($data);

Nightwatch::resume();
@endboostsnippet

## Integrations

### Slack Alerts

Configure Slack notifications for new exceptions and performance threshold breaches in the Nightwatch dashboard.

### Linear Issue Tracking

Link Nightwatch exceptions to Linear issues directly from the dashboard.

### MCP Server

Nightwatch provides an MCP server at `https://nightwatch.laravel.com/mcp` for AI-assisted debugging and analysis. The MCP server uses OAuth authentication.

If experiencing frequent re-authentication issues with direct HTTP MCP, use `npx mcp-remote` as a proxy for more reliable token refresh:

@boostsnippet("Nightwatch MCP via mcp-remote", "json")
{
    "mcpServers": {
        "nightwatch": {
            "command": "npx",
            "args": ["-y", "mcp-remote", "https://nightwatch.laravel.com/mcp"]
        }
    }
}
@endboostsnippet

### Cursor Plugin

Install `laravel/nightwatch-cursor-plugin` for IDE integration with Cursor.

## Monitoring Capabilities

Nightwatch tracks 11 event types:

| Event Type | Description |
|------------|-------------|
| Requests | HTTP request performance and status |
| Queries | Database query execution and timing |
| Jobs | Queue job processing |
| Scheduled Tasks | Cron and scheduled task runs |
| Commands | Artisan command execution |
| Exceptions | Application errors and stack traces |
| Outgoing Requests | External HTTP calls |
| Cache | Cache hit/miss/write events |
| Mail | Email sending events |
| Notifications | Notification dispatch events |
| Logs | Application log entries |

### N+1 Query Detection

Nightwatch automatically detects N+1 query issues. No additional configuration is required.

### Performance Monitors

Configure alerts for slow queries, slow requests, and slow jobs in the Nightwatch dashboard.

## Testing

Disable Nightwatch in tests to avoid recording test events:

@boostsnippet("Disable Nightwatch in phpunit.xml", "xml")
<phpunit>
    <php>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
</phpunit>
@endboostsnippet

## Common Pitfalls

- Forgetting to start the agent process — telemetry is buffered but not sent without a running agent.
- Not setting `NIGHTWATCH_TOKEN` — the agent cannot authenticate without it.
- Running in tests without disabling — wastes event quota on test data.
- Not using the stack channel for logging — log events will not appear in Nightwatch unless the `nightwatch` channel is included in `LOG_STACK`.
- Serverless deployments (e.g., Laravel Vapor) — the agent process cannot run alongside the app; use a separate VM or container for the agent.
