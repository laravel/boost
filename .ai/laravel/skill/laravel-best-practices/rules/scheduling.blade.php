@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Task Scheduling Best Practices

## Prevent Overlap

Use `withoutOverlapping()` for variable-duration tasks that are unsafe to run concurrently. The optional argument is the lock expiry in minutes, not a task timeout. The default is 24 hours, and too short an expiry permits overlap while the first run continues. Clear stale locks with `{{ $assist->artisanCommand('schedule:clear-cache') }}`. Tasks should still tolerate retries and partial execution.

## One Server

`onOneServer()` requires all scheduler nodes to share the default cache store, and it must support atomic locks (`database`, `memcached`, `dynamodb`, `redis`). Name scheduled closures first, especially the same closure with different parameters, so each has a distinct lock identity.

## Background Execution

Tasks due together run sequentially. `runInBackground()` stops a long independent task delaying later ones, but works only for `command()` and `exec()`, not closures. Ensure logging and failure monitoring for background processes.

## Environments

`environments(['production'])` is an operational safeguard, not authorization.

## Groups

Use `Schedule::daily()->onOneServer()->group(fn () => ...)` only when tasks genuinely share frequency or constraints.

## Bound Work Inside the Task

There is no `takeUntilTimeout()` scheduler method, and the scheduler does not kill tasks at a deadline. Bound work in the command or job: finite chunks, deadline checks, or queue jobs with timeouts. Use OS or process controls for hard termination.
