---
name: testing-best-practices
description: "Use when writing, adding, or reviewing Laravel tests (Pest or PHPUnit), including a whole test suite. Covers choosing coverage, naming and structure, assertions, test data, isolating dependencies, HTTP and security boundaries, suite performance, and test value."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$phpunit = $assist->project->php()->package('phpunit/phpunit');
$phpunitDocs = 'the PHPUnit '.($phpunit ? $phpunit->version().' ' : '').'documentation at `https://phpunit.de/documentation.html`';
@endphp
# Testing Best Practices

This file holds what most tests need. Each rule file covers one subject in depth; read one only when the rule index says it applies.

Use `search-docs` only when you are unsure of a specific {{ $pest ? 'Laravel or Pest' : 'Laravel' }} API, not to confirm syntax that nearby tests already use.@if(! $pest) Fetch {{ $phpunitDocs }} for PHPUnit API syntax under the same condition.@endif

This project uses {{ $pest ? 'Pest' : 'PHPUnit' }}. Follow the corresponding guidance in each rule.

## Consistency First

Read nearby tests before you choose syntax and organization.

A pattern repeated throughout the project is a convention, and project conventions take precedence over this skill. Follow them and give new tests the same structure.

These rules govern the tests you write now. An existing test that follows a project convention is not defective merely because it conflicts with this skill. Do not delete or rewrite it. If the convention has drawbacks, explain them and let the user decide.

## What to Test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework. A constrained relationship, cast, scope, or validation rule belongs to this project.
- Keep every test that can detect a distinct defect. When two tests detect the same defect, trim the higher-layer test to one case and report the duplication. Do not delete an existing test.
- Write a feature test first. Write a unit test only for logic that does not use the framework.
@if($pest && $assist->hasPackage('pestphp/pest-plugin-browser'))
- Write a browser test only for behavior in JavaScript that a feature test cannot reach. Put a browser test in `tests/Browser`, and call `assertNoJavaScriptErrors()` in it.
@elseif($assist->hasPackage('laravel/dusk'))
- Write a Dusk test only for behavior in JavaScript that a feature test cannot reach. Put a Dusk test in `tests/Browser`.
@else
- Write a feature test for every behavior reachable through a request. Real-browser tests require {{ $pest ? '`pestphp/pest-plugin-browser`' : '`laravel/dusk`' }} and a browser download, neither of which this project installs. Mention the package only if the user asks for a real-browser test.
@endif
@if($pest)
- Judge an architecture test by the convention it protects, not by the rules above. An `arch()` test declares a rule for an entire directory, such as the parent class of every model, the classes that may use an enum, or the methods every factory declares. It intentionally checks declarations and fails when a new file breaks the convention.
@endif
- Use the test tools that the project installs. Add a new test dependency, plugin, or browser only after the user asks for it.

## How to Apply

1. Read the code under test. Read the tests in the same directory. Identify every decision in the code.
2. Read at most the two or three rule files in the index that match the code under test. Do not read a file the index tells you to skip.
3. Report each defect in the code before you write a test. Examples are a method with no body, a policy that no action calls, and a write action with no validation. Test the actual behavior. Report the defect to the user.
4. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.
5. Check the tests against the checklist below and the rule files you read. Resolve every mismatch before completion.

## Checklist

- Every changed decision and applicable failure path has a test, and each test catches a distinct defect.
- An endpoint test covers authentication, authorization, other tenants, and validation where they apply.
- Each test creates its own mutable records with factory states that convey their meaning. {{ $pest ? '`beforeEach()`' : '`setUp()`' }} holds configuration only.
- Expected values are known values, not computed with the implementation's logic.
- A sort test creates records in an order different from the expected result.
- A write test asserts the response, database state, and side effects. A failure path asserts that none of them happened.
- Each fake names its classes. Time, randomness, sleep, and outbound HTTP are controlled.
- Each test passes alone and in any order.

## Rule Index

| Rule File | Read It When | Skip It When |
| --- | --- | --- |
| [`rules/endpoint-tests.md`](rules/endpoint-tests.md) | The code is reached through a route: authentication, authorization, tenants, validation, sorting, or browser tests | The code has no route |
| [`rules/test-data.md`](rules/test-data.md) | You create records with factories, states, relationships, or {{ $pest ? 'datasets' : 'data providers' }} | The test needs no records, or nearby tests show the factory states |
| [`rules/isolation.md`](rules/isolation.md) | The code sends mail, notifications, events, jobs, or HTTP requests, or depends on time or randomness | The code does none of these |
| [`rules/assertions.md`](rules/assertions.md) | You must choose an assertion that nearby tests do not show | Nearby tests already assert the same kind of subject |
| [`rules/naming.md`](rules/naming.md) | You create a test file with no neighboring tests to copy | Neighboring tests show the layout and names |
| [`rules/finding-features.md`](rules/finding-features.md) | You need a test framework feature that nearby tests do not use | Nearby tests already use what you need |
| [`rules/security.md`](rules/security.md) | The code renders user input, builds a query from input, or checks a privilege | The test has no escaping, injection, or privilege check |
| [`rules/performance.md`](rules/performance.md) | The user asks about suite speed or CI time | Any other time |
| [`rules/review.md`](rules/review.md) | The user asks you to review existing tests | You are writing tests; use the checklist above |
