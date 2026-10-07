@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Naming and Structure

## File Layout

- Name files `{ClassName}Test.php` at the class's relative path: `app/Actions/DeleteTeam.php` gets `tests/Unit/Actions/DeleteTeamTest.php`.
- Follow the project's fixture convention; otherwise use `tests/Fixtures/` and load by path. Move large literals into fixtures.

@if($pest)
## Test Function

Use whatever neighboring files use. If none exist:

- `it()` for behavior, named as a verb phrase.
- `test()` for a declarative fact (a policy grant, enum labels, serialized shape).

Stay with one style per file.

## Naming Tests

The name is a specification: the user-visible result and its cause. Name the behavior, not the method. Include the exact status code for API errors. Avoid `Given`/`When`/`Then`.

```php
it('returns 401 when no token is provided', function () { ... });
it('falls back to the default region when none is configured', function () { ... });
```
@else
## Test Class and Methods

Extend the project's base `TestCase`. Use the `test_` prefix or `#[Test]`, following neighboring files.

## Naming Tests

The name is a specification, words separated by underscores: the user-visible result and its cause. Name the behavior, not the method. Include the exact status code for API errors. Avoid `given`/`when`/`then`.

```php
public function test_unauthenticated_request_redirects_to_login(): void { ... }
public function test_returns_401_when_no_token_is_provided(): void { ... }
```
@endif

Use result verbs: `returns`, `renders`, `creates`, `dispatches`, `rejects`, `forbids`, `falls back`, `does not`.

@if($pest)
Avoid vague names (`it('works correctly')`) and method names (`it('handleMethod creates record')`).
@else
Avoid `test_store()`, `test_it_works()`, `test_validation()`.
@endif

## Grouping

@if($pest)
Use `describe()` when a file covers separate lifecycle actions, such as a controller's `index`, `show`, `store`, `update`, `destroy`. Skip it for a single action or flow, for input-only variations (use a dataset), or when it adds nesting without clarity.
@else
One test class per class under test, split into separate classes for separate lifecycle actions (`StoreOrderControllerTest`, `UpdateOrderControllerTest`). Use `#[Group]` only to select or skip tests in a run, not to structure a file.
@endif
