@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Reviewing Tests

Check every item. A passing test may still have no value: for each, name the defect it would catch.

Report findings; do not delete or rewrite a test without the user's approval. Report a suite-wide pattern once, not per file.

## Test Value

@if($pest)
Applies to behavioral tests. Architecture tests state a directory convention and are exempt.

@endif
- [ ] Each test covers observable behavior or a contract and survives a behavior-preserving refactor.
- [ ] No test asserts framework behavior. Project configuration (a constrained relation, cast, or scope) is the project's to test.
- [ ] Each test catches a distinct defect. A higher-layer duplicate shrinks to the one case proving the wiring.
- [ ] Every changed decision and applicable high-value failure mode has coverage.

## Names and Structure

- [ ] Files are named `{ClassName}Test.php` and mirror the class path.
- [ ] Names state the result, its cause, and the status code for API errors.
@if($pest)
- [ ] One declaration style per file; each `describe()` holds separate behavior.
@else
- [ ] Classes extend the project's base `TestCase`; each file uses `test_` prefix or `#[Test]` consistently.
@endif

## Coverage

- [ ] HTTP tests cover authentication, authorization, role, scope, and validation where applicable.
- [ ] Other-tenant requests get a status that does not confirm the record exists.
- [ ] The permission matrix lives in policy tests, not controller tests.
- [ ] Each validation rule has one test asserting the user-visible message; reduce duplicate matrices to one case, not zero.
- [ ] Rendered user input and dynamic query parts have security tests.

## Data and Determinism

- [ ] Each test creates its mutable records, and every record arranges behavior or supports an assertion.
@if($pest)
- [ ] `beforeEach()` holds configuration only.
@else
- [ ] `setUp()` holds configuration only.
@endif
- [ ] Factory states and relationships convey the data's meaning.
- [ ] `make()` is used only where the database is not needed.
- [ ] Time, randomness, sleep, and outbound HTTP are controlled.
- [ ] Each test passes alone and in any order.

## Assertions

- [ ] Expected values are known literals, not computed with the implementation's logic.
- [ ] Write tests assert the response, database state, and side effects.
- [ ] Each fake has one assertion and names classes unless asserting the complete result.
@if($pest)
- [ ] Each `expect()` chain stays on one subject.
@else
- [ ] Assertion groups stay on one subject and comparisons use `assertSame()`.
@endif
