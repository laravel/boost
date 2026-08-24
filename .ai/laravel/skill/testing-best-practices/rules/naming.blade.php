@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Naming and Structure

## The Layout of the Files

- Give each test file the name `{ClassName}Test.php`.
- Put each test file at the same relative path as the class under test. The class `app/Actions/DeleteTeam.php` gets the test `tests/Unit/Actions/DeleteTeamTest.php`.
- Put each fixture file in `tests/Fixtures/`, and load the fixture by its path.
- Keep a large literal value out of the test body. Put the value in a fixture file.
- Use the convention of the project for `declare(strict_types=1);`.

@if($pest)
## The Test Function

Use the test function that the other files in the same directory use. If no file exists, apply the two rules that follow:

- Use `it()` for the behavior of the code, and write the name as a verb phrase.
- Use `test()` for a declarative fact, such as a grant in a policy, the labels of an enum, or the shape of a serialized model.

Use one test function in one file.

## The Names of the Tests

The name of a test is a specification. Write the name in plain English. Give the result that the user can see, and give the condition that causes the result.

- Name the behavior. Do not name the method under test.
- Do not repeat the name of the class under test. The file name gives the class.
- Give the condition if the result does not make the condition clear.
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

The name of a test method is a specification. Write the name in words that are separated by an underscore. Give the result that the user can see, and give the condition that causes the result.

- Name the behavior. Do not name the method under test.
- Do not repeat the name of the class under test. The file name gives the class.
- Give the condition if the result does not make the condition clear.
- Give the exact status code in the name of a test for an API error.
- Do not write `given`, `when`, or `then` in the name.

```php
public function test_unauthenticated_request_redirects_to_login(): void { ... }
public function test_returns_401_when_no_token_is_provided(): void { ... }
public function test_valid_payload_creates_record_and_returns_201(): void { ... }
```
@endif

Use a verb that gives a result: `returns`, `renders`, `requires`, `creates`, `updates`, `deletes`, `dispatches`, `sends`, `queues`, `validates`, `forbids`, `rejects`, `ignores`, `prevents`, `falls back`, `handles`, or `does not`.

@if($pest)
The name `it('works correctly')` gives no result. The name `it('returns data')` gives no result. The name `it('handleMethod creates record')` gives a method and not a behavior. Do not write a name of these three types.
@else
The name `test_store()` gives no result. The name `test_it_works()` gives no result. The name `test_validation()` gives no result. Do not write a name of these three types.
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

## The Order of the Tests

Put the tests for an HTTP endpoint in this order. A failure then shows the first check that is not correct.

1. The authentication
2. The authorization of the resource or of the tenant
3. The restrictions of the role and of the permission
4. The constraints of the route and of the scope
5. The failures of the validation
6. The correct results
7. The edge cases and the negative results

Put the tests for a unit or for a feature that does not use HTTP in this order:

1. The correct results
2. The error results
3. The boundary values
