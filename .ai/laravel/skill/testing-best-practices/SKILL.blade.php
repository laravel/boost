---
name: testing-best-practices
description: "Use this skill to design tests in Laravel, in any test framework. Trigger when you decide what to test, when you select the coverage for an endpoint or a class, when you name or structure a test, when you review a test suite, or when you judge if a suite has too many or too few tests. Covers: what to leave untested, the names and the layout of the test files, arrange-act-assert, the correct assertion for each subject, the coverage of authentication and authorization and validation, the isolation of a tenant, the factories and the data providers, the fakes and the mocks, the control of time and of randomness, the security tests, and a review checklist. Do not use this skill for the syntax of Pest or of PHPUnit, because the framework skill and search-docs give the syntax."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Testing Best Practices

This skill gives the rules to design a test in Laravel. Each rule file tells you what to do and why. Use `search-docs` to get the syntax of an API.

This project uses {{ $pest ? 'Pest' : 'PHPUnit' }}. Each rule in this skill gives the guidance for {{ $pest ? 'Pest' : 'PHPUnit' }}.

## Consistency First

Read the tests that exist before you apply a rule. Use the pattern that these tests use. A pattern that is not the best is better than two different patterns.

Read the test files in the same directory, and find the convention for each item that follows:

@if($pest)
- the test function, `it()` or `test()`
@else
- the name of the test method, and the use of the `#[Test]` attribute
@endif
- the construction of a factory
- the setup of the authentication
- the layout of the files

Apply the convention that you find. Apply the rules in this skill only if no convention exists.

## What to Test

Read this section before you write a test.

- Test the behavior of the code. Do not test the implementation. A test must pass after a change to the implementation if the behavior stays the same.
- Test the code that makes a decision. A decision is a branch, a validation, a calculation, or an authorization.
- Do not test the code that only declares a value. Examples are a configuration array, a route definition, `$fillable`, and Blade markup. A test of such code fails only after a change to the declaration, and tells you nothing.
- Do not test the framework. Laravel tests Eloquent and the router.
- Make the number of tests equal to the size of the change. Cover the behavior and the failure modes that are important. Then stop.
- Write a feature test first. Write a unit test only for logic that does not use the framework.
@if($pest && $assist->hasPackage('pestphp/pest-plugin-browser'))
- Write a browser test only for behavior in JavaScript that a feature test cannot reach. Put a browser test in `tests/Browser`, and call `assertNoJavaScriptErrors()` in it.
@elseif(! $pest && $assist->hasPackage('laravel/dusk'))
- Write a Dusk test only for behavior in JavaScript that a feature test cannot reach. Put a Dusk test in `tests/Browser`.
@else
- Write a feature test for each behavior that a request can reach. A test in a real browser needs {{ $pest ? '`pestphp/pest-plugin-browser`' : '`laravel/dusk`' }} and a browser download, and this project installs neither of them. Tell the user about the package only if the user asks for a test in a real browser.
@endif
- Use the test tools that the project installs. Add a new test dependency, plugin, or browser only after the user asks for it.

## How to Apply

1. Read the code under test. Read the tests in the same directory. Identify each decision in the code.
2. Find each decision in the rule index. Read each rule file that the index gives.
3. Report each defect in the code before you write a test. Examples are a method with no body, a policy that no action calls, and a write action with no validation. Test the actual behavior, and report the defect to the user.
4. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.
5. Read your changes again. Compare the changes with each rule file that you read, and with `rules/review.md`.

## Rule Index

Most changes need more than one rule file.

| Subject | Rule file |
| --- | --- |
| A feature of the test framework that can already do the work | [`rules/finding-features.md`](rules/finding-features.md) |
| The layout of the files, the names of the tests, the groups, and the order | [`rules/naming.md`](rules/naming.md) |
| Arrange-act-assert, and the correct assertion for each subject | [`rules/assertions.md`](rules/assertions.md) |
| The coverage of an endpoint, the authentication, the authorization, the isolation of a tenant, and the validation | [`rules/http-tests.md`](rules/http-tests.md) |
| The factories, the owner of the test data, and the repeated input values | [`rules/test-data.md`](rules/test-data.md) |
| The fakes, the mocks, the outbound HTTP, the time, the randomness, and the database | [`rules/isolation.md`](rules/isolation.md) |
| The escaping, the injection, the access across tenants, and the checks of privilege | [`rules/security.md`](rules/security.md) |
| The settings of the environment and of the CI for a slow suite | [`rules/performance.md`](rules/performance.md) |
| The review of a test or of a suite | [`rules/review.md`](rules/review.md) |
