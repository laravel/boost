---
name: testing-best-practices
description: "Laravel test design and review. Use when selecting coverage, naming or structuring tests, choosing assertions or test data, isolating dependencies, testing HTTP or security boundaries, improving suite performance, or reviewing test value. Use framework guidance or search-docs for Pest and PHPUnit syntax."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Testing Best Practices

This skill gives the rules to design a test in Laravel. Each rule file tells you what to do and why. Use `search-docs` to get the syntax of an API of {{ $pest ? 'Laravel and of Pest' : 'Laravel' }}.@if(! $pest) Fetch `https://docs.phpunit.de/en/13.3/` for the syntax of an API of PHPUnit.@endif

This project uses {{ $pest ? 'Pest' : 'PHPUnit' }}. Each rule in this skill gives the guidance for {{ $pest ? 'Pest' : 'PHPUnit' }}.

## Consistency First

Read nearby tests before you choose syntax and organization.

Follow established conventions when they preserve the behavior required by this skill.

Use the project convention for each item that follows:

@if($pest)
- the use of `it()` or `test()`
@else
- the test method name and the use of `#[Test]`
@endif
- the construction of a factory
- the setup of the authentication
- the layout of the files

## What to Test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to the framework tests.
- Keep each test only when it can detect a distinct defect.
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

1. Read the code under test. Read the tests in the same directory. Identify every decision in the code.
2. Select every applicable branch in the rule index. Read every selected rule file.
3. Report each defect in the code before you write a test. Examples are a method with no body, a policy that no action calls, and a write action with no validation. Test the actual behavior. Report the defect to the user.
4. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.
5. Check every applicable item in `rules/review.md` and every selected rule file. Resolve every mismatch before completion.

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
