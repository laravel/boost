@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# How to Find a Feature of the Test Framework

@if($pest)
Pest adds a feature faster than this skill can list it. This skill does not repeat the documentation. Find the feature that does the work before you write the code by hand.

- Give `search-docs` the name of the capability that you need, and not the name of a function that you remember. `search-docs` gives the features of the version that the project installs.
- Fetch `https://pestphp.com/llms.txt` with a web request for the complete list of the features and for the new features of each release.
- Tell the user that a feature does not exist in the installed version, if a search gives no result. Do not write an API that you did not confirm.

Search for a feature in this table before you write the code by hand.

| Work that you need | Term to search for |
| --- | --- |
| Run one test with many input values | datasets, bound datasets |
| Assert over many values or over a collection | higher-order expectations |
| Remove the same setup from each test in a file | hooks, higher-order tests |
| Apply a convention to the complete codebase | architecture testing |
| Measure if the suite finds a defect | mutation testing |
| Find code with no types | type coverage |
| Reduce the time of a slow suite | parallel, sharding, profiling |
| Run only the tests that a change affects | Test Impact Analysis, `--tia` |
| Assert that a value has a known format | validation expectations |
| Run one test while you debug | filtering, `--bail`, `--dirty` |
@else
PHPUnit and Laravel give a feature for most of the work that a test needs. This skill does not repeat the documentation. Find the feature that does the work before you write the code by hand.

- Give `search-docs` the name of the capability that you need, and not the name of a method that you remember. `search-docs` gives the testing documentation of Laravel for the version that the project installs.
- Fetch `https://phpunit.de/documentation.html` with a web request for the attributes, the assertions, and the options of the command line of PHPUnit specific versions.
- Tell the user that a feature does not exist in the installed version, if a search gives no result. Do not write an API that you did not confirm.

Search for a feature in this table before you write the code by hand.

| Work that you need | Term to search for |
| --- | --- |
| Run one test method with many input values | data provider, `#[DataProvider]`, `#[TestWith]` |
| Run one test only after another test passes | `#[Depends]` |
| Select or skip a set of tests in one run | `#[Group]`, `--group`, `--exclude-group` |
| Skip a test on a version or on a missing extension | `#[RequiresPhp]`, `#[RequiresPhpExtension]` |
| Find a test that depends on the order of the run | `--order-by=random` |
| Reduce the time of a slow suite | ParaTest, `--cache-result` |
| Stop the run at the first failure while you debug | `--stop-on-failure`, `--filter` |
@endif

## The Assertions of Laravel

Laravel adds an assertion for each part of the framework. Search for the assertion before you build a check by hand. Examples are `assertDatabaseHas()`, `assertModelExists()`, `assertSoftDeleted()`, the response assertions such as `assertRedirectToRoute()` and `assertJsonPath()`, and the fake assertions such as `Queue::assertPushed()` and `Notification::assertSentTo()`.

A check that you build by hand fails with `false is not true`, and that message names nothing. The assertion of the framework names the table, the value, or the response that is not correct, so the failure tells you what to fix.

```php
// The failure says that false is not true.
// Instead of this
@if($pest)
expect(User::where('email', 'taylor@laravel.com')->exists())->toBeTrue();
@else
$this->assertTrue(User::where('email', 'taylor@laravel.com')->exists());
@endif

// Use this
// The failure names the table and the attributes that it did not find.
$this->assertDatabaseHas('users', ['email' => 'taylor@laravel.com']);
```
