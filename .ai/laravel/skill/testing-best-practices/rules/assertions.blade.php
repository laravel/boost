@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
$phpunit = $assist->project->php()->package('phpunit/phpunit');
$phpunitDocs = 'the PHPUnit '.($phpunit ? $phpunit->version().' ' : '').'documentation at `https://phpunit.de/documentation.html`';
@endphp
# Assertions

## Arrange, Act, Assert

Setup, one action, assertions, separated by blank lines. Keep each test self-contained; never reuse values from another test.

## Finding the Right Assertion

Identify the subject, then find an assertion designed for it so a failure names the wrong value.

1. Search Laravel's assertions for framework subjects (responses, database, session, models, queues, events, mail, notifications).
2. Fetch {{ $pest ? '`https://pestphp.com/docs/expectations.md` for Pest expectations' : $phpunitDocs.' for PHPUnit assertions' }} for values, types, formats, and shapes.
3. Hand-build a check only if none exists.
4. Confirm the name in the docs; never use an unconfirmed assertion.

@if($pest)
| Subject | Assertion to use |
| --- | --- |
| Return value, object state, transformation | an `expect()` chain |
| HTTP status, JSON, session, Inertia | a Laravel response assertion |
| Database state | a Laravel database assertion |
| Model existence | `assertModelExists($model)` over `assertDatabaseHas('users', ['id' => $user->id])` |

Use a PHPUnit assertion only when no Pest expectation or Laravel assertion fits.
@else
| Subject | Assertion to use |
| --- | --- |
| Return value, object state, transformation | `assertSame()` or the type's assertion |
| HTTP status, JSON, session, Inertia | a Laravel response assertion |
| Database state | a Laravel database assertion |
| Model existence | `assertModelExists($model)` over `assertDatabaseHas('users', ['id' => $user->id])` |

Prefer `assertSame()` to `assertEquals()`; it compares type too.
@endif

Assert each fact once; skip a 200 check before `assertSee`, which already proves the page rendered.

## Named Response Assertions

Use `assertNotFound()` rather than `assertStatus(404)`; the failure names the broken contract.

@if($pest)
Keep an `expect()` chain on one subject; start a new chain when the subject changes.
@else
Group assertions by subject; start a new group when it changes.
@endif

@if($pest5)
## Format Expectations

Prefer Pest's format expectations (email, URL, UUID, IP, and others, each with `not`) over regular expressions for clearer failures.

@endif
## Assert a Known Value

Write the expected value literally or derive it differently. Computing it with the implementation's logic passes when that logic is wrong.

@if($pest)
```php
$expected = now()->subHours(24)->floorSeconds(30)->toJson();
expect($from)->toBe($expected);

travelTo('2025-01-01 00:00:00');
expect($from)->toBe('2024-12-31T00:00:00.000000Z');
```
@else
```php
$expected = now()->subHours(24)->floorSeconds(30)->toJson();
$this->assertSame($expected, $from);

$this->travelTo('2025-01-01 00:00:00');
$this->assertSame('2024-12-31T00:00:00.000000Z', $from);
```
@endif

The first pair mirrors the implementation; the second fixes input and asserts a known value.

## Assert the Complete Result

A status code is not a write's full result. Also assert what changed: response or return value, database state, dispatched jobs and events, sent notifications and mail. On the failure path, assert none of these happened. A bare `assertOk()` passes even when nothing was saved.
