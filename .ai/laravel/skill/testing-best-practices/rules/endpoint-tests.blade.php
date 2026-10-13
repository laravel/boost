@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$browserPlugin = $pest && $assist->hasPackage('pestphp/pest-plugin-browser');
$dusk = $assist->hasPackage('laravel/dusk');
@endphp
# Endpoint Tests

Fetch `https://laravel.com/framework/docs/http-tests` for request helpers, authentication helpers, and response assertions. Confirm an assertion's name before using it. Pick the named assertion that matches the subject (status, header, redirect, JSON, session, validation error, view) so a failure identifies the wrong value.

## Coverage

Write a test for each applicable case:

- Missing or invalid authentication.
- A different tenant, team, or organization.
- Insufficient role or permission.
- A route or scope constraint not satisfied.
- Failed validation.
- A valid request. Assert both the response and the persisted state.

Assert the app's actual behavior, not a generic status: an API returns `401`, a browser endpoint redirects to sign-in.

For cross-tenant requests, use `404` rather than `403` when the tenant must not learn the record exists.

## Authorization Belongs at the Policy Level

An HTTP test proves the endpoint authorizes, but not which mechanism refused: middleware, a policy, and `abort()` can all return `403`.

- Assert the full permission matrix against the policy or gate, so a failure names the broken rule.
- Keep one HTTP test for one refused role.
- Reuse the project's helper for asserting gate ability and arguments, if one exists.

@if($browserPlugin || $dusk)
## Browser Tests

Only for JavaScript behavior HTTP tests cannot reach (modals, drag-and-drop, live search, client-side validation). They are slower and flakier.

- Assert what the user sees and what was saved to the database.
- Wait for a state, never a fixed number of seconds.
@if($browserPlugin)
- Call `assertNoJavaScriptErrors()` in each test.

### Running Them

- Put them in `tests/Browser`: `{{ $assist->binCommand('pest tests/Browser') }}`, add `--parallel` for the full suite.
- `{{ $assist->binCommand('pest --debug') }}` pauses on failure; `--headed` watches a passing run; `--browser firefox|safari` changes the default Chrome.
- Requires Playwright and a browser; follow the plugin docs for installation.
- Fetch `https://pestphp.com/docs/browser-testing` for interactions, assertions, and devices.

### Pitfalls

- Element waits default to five seconds. Raise with `pest()->browser()->timeout(10000)` in `Pest.php`, not sleeps.
- Apply `RefreshDatabase` to browser tests in `Pest.php`, since leftover records break later tests.
- Add `tests/Browser/Screenshots` to `.gitignore`.
- Give `withKeyDown()` a key code such as `KeyA`; `'a'` yields the lowercase character regardless of modifier.
- Interact inside the `withinFrame()` callback; outside it does not reach the frame.
@else
- Put them in `tests/Browser` and run `{{ $assist->artisanCommand('dusk') }}`. It needs ChromeDriver from `{{ $assist->artisanCommand('dusk:install') }}`.
- Fetch `https://laravel.com/framework/docs/dusk` for selectors, interactions, and assertions.
@endif
@endif

## Validation

- One test per rule when each failure is a separate contract; one empty-payload test for several required fields.
- Assert the message text the user gets; a wrong message is a defect.
@if($pest)
- Use a dataset for inputs sharing setup and assertions.
@else
- Use `#[DataProvider]` for inputs sharing setup and assertions, `#[TestWith]` for a small set.
@endif

Send invalid input through the app and assert the error. Do not assert that a rules array contains a string, since that tests the declaration, not behavior. Only do so for a rule no request can reach, and state the reason in the test.

### Which Layer Owns Which Case

The rule-class test owns the pass/fail value matrix. The endpoint test proves the endpoint applies the rule and the user sees the message. If both hold the matrix, move it to the rule-class test but keep one endpoint case, otherwise the rule-class test still passes when the request omits the rule. The same split applies to policies, scopes, and other classes a request calls.
