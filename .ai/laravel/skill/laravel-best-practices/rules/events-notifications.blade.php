@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Events and Notifications Best Practices

## Event Discovery

Laravel discovers listeners from type-hinted event arguments on `handle()` / `__invoke()`. Register manually only when discovery is disabled, the listener is outside the configured directories, or explicit registration is clearer.

Cache discovery in production deploys with `{{ $assist->artisanCommand('optimize') }}` or `{{ $assist->artisanCommand('event:cache') }}`, and rebuild whenever listeners change.

## `ShouldDispatchAfterCommit`

Implement `Illuminate\Contracts\Events\ShouldDispatchAfterCommit` on events dispatched inside transactions. Dispatch waits for the commit and is discarded on rollback. This affects synchronous and queued listeners alike, not just queue timing. For model observers, implement `Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit`.

## Queue Slow Notifications

Implement `ShouldQueue` (with `Queueable`) on notifications that call external services (mail, SMS, Slack) unless the operation needs immediate completion or failure feedback.

A queued notification sent inside a transaction can run before commit. Use `->afterCommit()` on the notification, or the connection's `after_commit` option, when delivery depends on committed data. It has no effect on synchronous notifications.

Use `viaQueues()` to route channels to separate queues when latency or priority differs.

## On-Demand Notifications

Use `Notification::route('mail', 'admin@example.com')->notify(...)` instead of dummy models for arbitrary recipients.

## Locale

Implement `HasLocalePreference::preferredLocale()` on notifiable models so notifications and mailables use the recipient's locale, including when queued. An explicit `locale()` call overrides it.
