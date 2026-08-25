@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Naming and Structure

## The Layout of the Files

- Give each test file the name `{ClassName}Test.php`.
- Put each test file at the same relative path as the class under test. The class `app/Actions/DeleteTeam.php` gets the test `tests/Unit/Actions/DeleteTeamTest.php`.
- Follow the convention of the project for the fixture files. Put each fixture file in `tests/Fixtures/` if the project has no convention, and load the fixture by its path.
- Keep a large literal value out of the test body. Put the value in a fixture file.

@if($pest)
## The Test Function

Use the test function that the other files in the same directory use. If no file exists, apply the two rules that follow:

- Use `it()` for the behavior of the code, and write the name as a verb phrase.
- Use `test()` for a declarative fact, such as a grant in a policy, the labels of an enum, or the shape of a serialized model.

Use one Pest declaration style in each file. Use either `it()` or `test()` consistently.

## The Names of the Tests

The name of a test is a specification. Give the result that the user can see, and give the condition that causes it.

- Name the behavior, and not the method under test. The file name already gives the class.
- Give the exact status code in the name of a test for an API error.
- Do not write `Given`, `When`, or `Then` in the name.

```php
it('returns 401 when no token is provided', function () { ... });
it('does not include deployments from deleted environments', function () { ... });
it('falls back to the default region when none is configured', function () { ... });
```
@else
## The Test Class and the Test Methods

- Extend the base `TestCase` of the project in each test class.
- Give each test method the prefix `test_`, or add the `#[Test]` attribute to the method. Use the convention of the other files in the same directory.

## The Names of the Tests

The name of a test method is a specification. Write the words separated by an underscore. Give the result that the user can see, and give the condition that causes it.

- Name the behavior, and not the method under test. The file name already gives the class.
- Give the exact status code in the name of a test for an API error.
- Do not write `given`, `when`, or `then` in the name.

```php
public function test_unauthenticated_request_redirects_to_login(): void { ... }
public function test_returns_401_when_no_token_is_provided(): void { ... }
public function test_valid_payload_creates_record_and_returns_201(): void { ... }
```
@endif

Use a verb that gives a result, such as `returns`, `renders`, `creates`, `dispatches`, `rejects`, `forbids`, `falls back`, or `does not`.

@if($pest)
Do not write `it('works correctly')` or `it('returns data')`, because neither gives a result. Do not write `it('handleMethod creates record')`, because it gives a method and not a behavior.
@else
Do not write `test_store()`, `test_it_works()`, or `test_validation()`, because none of them gives a result.
@endif

## The Groups

@if($pest)
Use `describe()` if one file covers separate actions in a lifecycle. An example is a controller with the actions `index`, `show`, `store`, `update`, and `destroy`.

Do not use `describe()` in these three conditions:

- The file covers one action or one flow.
- The tests are different only in the input value. Use a dataset instead.
- The group adds a level but does not make the file easier to read.
@else
Write one test class for each class under test. Write a separate test class if one file covers separate actions in a lifecycle, such as `StoreOrderControllerTest` and `UpdateOrderControllerTest`.

Use the `#[Group]` attribute to mark the tests that a run must select or must skip. Do not use a group to give structure to a file, because a class gives the structure.
@endif
