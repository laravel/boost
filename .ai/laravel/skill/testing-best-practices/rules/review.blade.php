@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# The Review of the Tests

Check every item in this file. A test that passes can have no value. For each test, state the defect that the test finds.

## The Value of the Test

- [ ] Each test covers a decision in the code, and not a declaration such as a configuration array, a route, `$fillable`, or markup.
- [ ] Each test passes after a change to the implementation that keeps the behavior.
- [ ] No test asserts the behavior of the framework.
- [ ] No test repeats the coverage of a different test.
- [ ] The number of tests is equal to the size of the change.

## The Names and the Structure

- [ ] Each file has the name `{ClassName}Test.php` and the relative path of the class under test.
- [ ] Each name gives a result and the condition that causes the result.
- [ ] Each name of a test for an API error gives the status code.
@if($pest)
- [ ] Each file uses one test function, `it()` or `test()`.
- [ ] Each `describe()` group holds separate behavior.
@else
- [ ] Each test method has the prefix `test_` or the `#[Test]` attribute, and the file uses one of the two conventions.
- [ ] Each test class extends the base `TestCase` of the project.
@endif

## The Coverage

- [ ] The HTTP tests cover the authentication, the authorization, the role, the scope, and the validation, if the case applies.
- [ ] A request for a record of a different tenant gets `404`.
- [ ] The complete matrix of the permissions is in the tests of the policy, and not in the tests of the controller.
- [ ] Each rule of the validation has one test, and the test asserts the message that the user gets.
- [ ] The output of a user and each dynamic part of a query have a security test.

## The Data and the Determinism

- [ ] Each test makes the data that it uses.
@if($pest)
- [ ] Each `beforeEach()` holds a configuration only.
@else
- [ ] `setUp()` holds a configuration only, and a private method makes each record.
@endif
- [ ] Each factory state and each relationship gives the meaning of the data.
- [ ] No test makes a record that no assertion uses.
- [ ] Each call to `make()` is in a test that does not need the database.
- [ ] The time, the randomness, the sleep, and the outbound HTTP are under control.
- [ ] Each test passes alone, and passes in the complete suite in any order.

## The Assertions

- [ ] Each expected value is a known value, and the test does not calculate the value with the logic of the implementation.
- [ ] Each test of a write operation asserts the response, the state in the database, and the side effects.
- [ ] Each fake has one assertion.
- [ ] Each fake gives the class names, or the test asserts the complete result.
@if($pest)
- [ ] Each `expect()` chain stays on one subject.
@else
- [ ] Each group of assertions stays on one subject, and each comparison uses `assertSame()`.
@endif

## The Defects to Report

A review can find a defect in the code and not in the tests. Report each defect that follows to the user. Do not write a test that makes the defect correct behavior.

- [ ] A method with no body.
- [ ] A policy that exists, but that no action calls.
- [ ] A write action with no validation.
- [ ] A status code or a response shape that is different from the shape of a similar endpoint.
