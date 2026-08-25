@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# The Review of the Tests

Check every item in this file. A test that passes can have no value. For each test, state the defect that the test finds.

A review reports. Report each item that you find, and do not delete a test and do not rewrite a test without approval of the user. An item that the project repeats throughout the suite is a convention, and the report names the pattern once instead of each file that follows it.

## The Value of the Test

@if($pest)
Apply this section to a test of behavior. An architecture test states a convention for a directory, and the items that follow do not apply to it.

@endif
- [ ] Each test covers observable behavior or an application contract, and passes after a change to the implementation that keeps the behavior.
- [ ] Each tested declaration is exercised through behavior, and no test asserts the behavior of the framework. A test of what this project configures, such as a relation with a constraint, a cast, or a scope, belongs to this project.
- [ ] Each test detects a distinct defect that no other test covers. A duplicate shrinks at the higher layer to the one case that proves the wiring.
- [ ] Every changed decision and each applicable high-value failure mode has coverage.

## The Names and the Structure

- [ ] Each file has the name `{ClassName}Test.php` and the relative path of the class under test.
- [ ] Each name gives a result and the condition that causes it, and gives the status code for an API error.
@if($pest)
- [ ] Each file uses one declaration style consistently, and each `describe()` group holds separate behavior.
@else
- [ ] Each test class extends the base `TestCase` of the project, and each file uses either the prefix `test_` or the `#[Test]` attribute consistently.
@endif

## The Coverage

- [ ] The HTTP tests cover the authentication, the authorization, the role, the scope, and the validation, if the case applies.
- [ ] A request for a record of a different tenant gets a status code that does not confirm that the record exists.
- [ ] The complete matrix of the permissions is in the tests of the policy, and not in the tests of the controller.
- [ ] Each rule of the validation has one test, and the test asserts the message that the user gets. A duplicate of a matrix that a unit test owns shrinks to one case, and it is not deleted.
- [ ] The output of a user and each dynamic part of a query have a security test.

## The Data and the Determinism

- [ ] Each test creates its mutable records directly or through a helper that it calls, and every created record arranges the behavior or supports an assertion.
@if($pest)
- [ ] Each `beforeEach()` holds a configuration only.
@else
- [ ] `setUp()` holds a configuration only.
@endif
- [ ] Each factory state and each relationship gives the meaning of the data.
- [ ] Each call to `make()` is in a test that does not need the database.
- [ ] The time, the randomness, the sleep, and the outbound HTTP are under control.
- [ ] Each test passes alone, and passes in the complete suite in any order.

## The Assertions

- [ ] Each expected value is a known value, and the test does not calculate the value with the logic of the implementation.
- [ ] Each test of a write operation asserts the response, the state in the database, and the side effects.
- [ ] Each fake has one assertion, and gives the class names unless the test asserts the complete result.
@if($pest)
- [ ] Each `expect()` chain stays on one subject.
@else
- [ ] Each group of assertions stays on one subject, and each comparison uses `assertSame()`.
@endif

## The Defects to Report

A review can find a defect in the code and not in the tests. Report each defect that follows, and do not write a test that makes the defect correct behavior.

- [ ] A method with no body.
- [ ] A policy that exists, but that no action calls.
- [ ] A write action with no validation.
- [ ] A status code or a response shape that is different from the shape of a similar endpoint.
