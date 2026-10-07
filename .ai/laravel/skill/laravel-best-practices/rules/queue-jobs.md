# Queue and Job Best Practices

## Reservation Time Must Exceed Execution Time

For drivers using `retry_after`, set it above the longest job `$timeout` and keep the worker's `--timeout` several seconds below it. Otherwise another worker reserves the job while the first is still running. SQS uses the queue's visibility timeout instead. Workers can still die after side effects but before acknowledging, so make important jobs idempotent.

## Back Off Transient Failures

Use increasing delays (`public $backoff = [1, 5, 10];` with `$tries`) for dependencies that need time to recover. Do not retry permanent validation or business-rule failures.

Rate-limit and exception-throttling middleware release jobs back to the queue, and released attempts may still count toward `$tries`. Set `$tries` or `retryUntil()` to cover the intended retry window.

## Time-Based Retries

`retryUntil()` replaces attempt counting with a deadline and takes precedence over `$tries`, so `$tries = 0` is not needed. Other limits like max exceptions still apply.

## Unique Jobs

`ShouldBeUnique` (with `uniqueId()` / `$uniqueFor`) prevents duplicate queued instances. It uses a cache lock, so it is not a substitute for idempotent processing or a DB constraint. All dispatchers need a shared cache with lock support, and uniqueness does not apply to jobs inside batches.

Use `ShouldBeUniqueUntilProcessing` only when the lock should release as processing starts, allowing a new instance while the first runs.

## Terminal Failure

Implement `failed()` to update state, alert someone, or record domain context after all attempts are exhausted. Do not log every failure per job; the queue already reports them. `failed()` runs on a fresh job instance, so mutations made in `handle()` are not available.

## Rate Limit External Calls

Use `RateLimited('name')` middleware when jobs share a third-party quota. Define the named limiter and choose release delays and attempt limits together.

## Batches

`Bus::batch()` coordinates a group with `then`/`catch`/`finally` callbacks. It is not a transaction: completed jobs are not rolled back. One failure cancels the batch by default; use `allowFailures()` only when partial failure is acceptable.

## Horizon

Horizon provides monitoring, balancing, and metrics for Redis queues only.
