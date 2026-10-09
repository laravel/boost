@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
@endphp
# How to Find Test Framework Features

@if($pest)
Pest adds features faster than this skill lists them. Look for an existing feature before hand-building behavior.

- Give `search-docs` the capability you need, not a function name you remember.
- Fetch `https://pestphp.com/llms.txt` for the full feature list and release additions.
- If nothing is found, tell the user the installed version lacks it. Never write an API you have not confirmed.

| Work that you need | Term to search for |
| --- | --- |
| One test with many inputs | datasets, bound datasets |
| Assert over many values or a collection | higher-order expectations |
| Remove shared setup from a file | hooks, higher-order tests |
| Enforce a convention across the codebase | architecture testing |
| Check the suite catches defects | mutation testing |
| Find untyped code | type coverage |
| Speed up a slow suite | parallel, profiling |
@if($pest5)
| Split the suite across CI jobs | sharding, `--update-shards` |
| Run only tests a change affects | Test Impact Analysis, `--tia` |
| Assert a value has a known format | validation expectations |
@endif
| Run one test while debugging | filtering, `--bail`, `--dirty` |
@else
PHPUnit and Laravel cover most needs. Look for an existing feature before hand-building behavior.

- Give `search-docs` the capability you need, not a method name you remember.
- Fetch `https://phpunit.de/documentation.html` for version-specific attributes, assertions, and CLI options.
- If nothing is found, tell the user the installed version lacks it. Never write an API you have not confirmed.

| Work that you need | Term to search for |
| --- | --- |
| One test with many inputs | data provider, `#[DataProvider]`, `#[TestWith]` |
| Run a test only after another passes | `#[Depends]` |
| Select or skip sets of tests | `#[Group]`, `--group`, `--exclude-group` |
| Skip on version or missing extension | `#[RequiresPhp]`, `#[RequiresPhpExtension]` |
| Find order-dependent tests | `--order-by=random` |
| Speed up a slow suite | ParaTest, `--cache-result` |
| Stop at first failure while debugging | `--stop-on-failure`, `--filter` |
@endif

## Built-in Laravel Assertions

Fetch `https://laravel.com/framework/docs/testing` and search for an assertion before hand-building a check, e.g. `assertDatabaseHas()`, `assertModelExists()`, `assertSoftDeleted()`, `assertRedirectToRoute()`, `assertJsonPath()`, `Queue::assertPushed()`, `Notification::assertSentTo()`.

A hand-built check fails with `false is not true`; a framework assertion names the wrong table, value, or response.

```php
// Avoid
@if($pest)
expect(User::where('email', 'taylor@laravel.com')->exists())->toBeTrue();
@else
$this->assertTrue(User::where('email', 'taylor@laravel.com')->exists());
@endif

// Prefer
$this->assertDatabaseHas('users', ['email' => 'taylor@laravel.com']);
```
