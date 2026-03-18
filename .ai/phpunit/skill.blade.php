---
applyTo: "tests/**"
---

# PHPUnit Test Design Methodology

## Test Philosophy

Tests verify behaviour, not implementation. Before writing any test, ask: *if the implementation changed but the outcome stayed the same, would this test still pass?* It should.

Tests must be **FIRST**: Fast, Isolated, Repeatable, Self-validating, Timely. Each test method verifies one thing. A failing test should identify the broken behaviour without ambiguity.

Structure every test using **AAA**: arrange preconditions, act on the SUT, assert the expected outcome — separated by blank lines.

---

## What to Test

For any feature, cover:

- Happy path — expected output, side effects, response status
- Unauthenticated access — redirect or 401
- Unauthorised access — 403, tested against the policy/gate directly, not through a controller that has other logic
- Each required field missing
- Each boundary violation (min, max, type)
- Uniqueness and foreign key constraints — tested behaviourally via HTTP, not by asserting rule strings
- Edge cases — null optionals, empty collections, boundary values

**Behavioural over structural.** Prefer submitting invalid input through the stack and asserting the error, over asserting that a rules array contains a particular string. Structural assertions are only acceptable for methods that cannot be reached
via HTTP; document why.

**One assertion purpose per test.** Redundant assertions — checking status 200 before `assertSee`, asserting a value that a prior assertion already implies — add noise without signal. Remove them.

---

## Test Isolation

- Prefer private factory methods over `setUp()` for building the SUT. `setUp()` runs for every test and its allocated objects persist in memory until the suite completes; factory methods use locals that are freed immediately.
- Authorization tests: test policies directly on the policy class, or via a minimal dedicated test route. Do not test authorization by routing through a real controller — a 403 could come from middleware, the policy, or a manual `abort()`, and you
cannot tell which.
- **Flag gaps and seek direction before writing tests.** Before writing any tests for a class, audit it for discrepancies between intent and implementation. If gaps are found, **stop, report them to the user, and ask what to do** — do not assume and
proceed. Common gaps:
- *Empty stub methods* — a method with no body (or just `//`) has no testable behaviour. Ask whether to skip, implement, or remove it.
- *Authorization gaps* — a policy exists but the controller action never calls `$this->authorize()`. Ask whether to enforce the policy or intentionally allow all authenticated users.
- *Other gaps* — missing validation on a mutating action, inconsistent response codes, a relation loaded in some branches but not others.

Once the user has confirmed what the correct behaviour should be, write tests that reflect their decision. Use `// TODO:` (not `// NOTE:`) for any remaining known issues so they appear in the IDE task list. Example: `// TODO: MethodName() is an
unimplemented stub — add tests when implemented.`
- Use `#[CoversClass(ClassName::class)]` on every test class. This enforces test boundaries, prevents accidental coverage, and scopes `--covers` filtering.
- Use `#[Small]`, `#[Medium]`, `#[Large]` to categorise by scope and execution time.
- Use data providers (`#[DataProvider]` or `#[TestWith]`) for boundary tests rather than repeating near-identical test methods.

---

## Coverage-Guided Testing

Coverage reports show which lines executed — not whether behaviour was verified. A fully green report can hide a suite full of hollow assertions.

Use coverage to find untested **branches** (the `false` side of a condition, an unhandled exception path, a guard clause never triggered), not to reach a line-count target. For each uncovered branch, identify what input or state would cause it to
execute, then write a test that creates that state and asserts the resulting outcome.

Coverage at 80% with meaningful assertions is more valuable than 100% with hollow ones.

When using a coverage report as the starting point, generate it first, sort by lowest branch coverage, prioritise critical paths (authorisation, mutation, financial logic), then author tests. Do not author tests bottom-up from the report — use it as
a gap-finder, then design tests from the user/behaviour perspective.

---

## Naming

Test method names are executable specifications. They should read as a sentence:

- `test_unauthenticated_request_redirects_to_login`
- `test_non_admin_user_is_forbidden`
- `test_label_too_short_fails_validation`
- `test_valid_payload_creates_record_and_returns_201`

Avoid `test_store`, `test_it_works`, `test_validation`. The name should describe the scenario and expected outcome, not the method being called.
