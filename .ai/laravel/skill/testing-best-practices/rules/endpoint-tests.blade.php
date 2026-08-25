@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$browserPlugin = $pest && $assist->hasPackage('pestphp/pest-plugin-browser');
$dusk = $assist->hasPackage('laravel/dusk');
@endphp
# Endpoint Tests

## How to Write the Test

Fetch `https://laravel.com/framework/docs/http-tests` for the request helpers, the authentication helpers, and the response assertions. Confirm the name before you use it, and do not guess an assertion.

Choose the assertion from the subject of the check: the status, a header, a redirect, the JSON body, the session, a validation error, or the view. Laravel gives a named assertion for each subject, and that assertion reports the value that is not correct.

## The Coverage of an Endpoint

Write a test for each case that follows, if the case applies to the endpoint:

- The request has no authentication, or the authentication is not valid.
- The request comes from a different tenant, team, or organization.
- The user has an insufficient role or an insufficient permission.
- The request does not meet a constraint of the route or of the scope.
- The request fails the validation.
- The request is correct. Assert the response and the state that the request saves.

Assert the actual behavior of the application, and not a general status code. An API returns `401` for a token that is absent or not valid, and a browser endpoint redirects the user to the sign-in route.

## The Isolation of a Tenant

Assert the status code that the application returns to a different tenant. Return `404` and not `403` if the existence of a record is information that one tenant must not get about a different tenant, because `403` confirms that the record exists.

## Test the Authorization at the Policy

An HTTP test shows that the endpoint calls the authorization. It cannot show which mechanism refused the request, because middleware, a policy, or a call to `abort()` can each return `403`.

- Assert the complete matrix of the permissions against the policy or the gate. A failure then names the rule that is not correct.
- Write one HTTP test for one refused role, which shows that the endpoint calls the authorization.
- Use the helper of the project that asserts the ability and the arguments of the gate, if such a helper exists.

@if($browserPlugin || $dusk)
## The Browser Tests

Write a browser test only for behavior in JavaScript that an HTTP test cannot reach, such as a modal, a drag, a live search, or a validation that runs in the client. A browser test is slower than an HTTP test, and it fails for reasons that are not in the code under test.

- Assert the state that the user can see, and assert the state in the database that the interaction saves.
- Wait for the state that the test needs. Do not wait for a number of seconds, because the wait then fails on a slower machine.
@if($browserPlugin)
- Call `assertNoJavaScriptErrors()` in each browser test. An error in the console is a defect.

### Where a Browser Test Lives and How to Run It

The plugin runs a browser test as a normal Pest test, so a browser test needs no separate suite. Put each browser test in `tests/Browser`, which keeps the slow tests apart from the fast tests and lets one run cover a directory.

- Run a browser test with `{{ $assist->binCommand('pest tests/Browser') }}`, and add `--parallel` for the complete suite.
- Run `{{ $assist->binCommand('pest --debug') }}` to open the window of the browser and to pause at a failure. Use `--headed` to watch a run that passes.
- Add `--browser firefox` or `--browser safari` to run the test in a different browser. The default browser is Chrome.
- The run needs a browser on the machine. Install it with `npm install playwright@latest && npx playwright install`, and use `npx playwright install --with-deps` in the CI.
- Fetch `https://pestphp.com/docs/browser-testing` for the interactions, the assertions, and the devices that the plugin gives.

### The Gotchas of a Browser Test

- The plugin waits five seconds for an element. Raise the value with `pest()->browser()->timeout(10000)` in `Pest.php` for a page that is slower, and do not add a wait for a number of seconds to the test.
- Apply `RefreshDatabase` to the browser tests in `Pest.php`. A browser test hits the application through a real request, and the records that it leaves break the next test.
- Add `tests/Browser/Screenshots` to `.gitignore`. A failure writes a screenshot, and the file is not part of the repository.
- Give `withKeyDown()` a key code, such as `KeyA`. A letter such as `'a'` gives the lowercase character, whatever modifier the test holds.
- Interact inside the callback of `withinFrame()`. An interaction outside the callback does not reach the frame.
@else
- Put each browser test in `tests/Browser`, which is the suite that Dusk runs.
- Run the browser tests with `{{ $assist->artisanCommand('dusk') }}`. The run needs a ChromeDriver, and `{{ $assist->artisanCommand('dusk:install') }}` downloads it.
- Fetch `https://laravel.com/framework/docs/dusk` for the selectors, the interactions, and the assertions of Dusk.
@endif
@endif

## The Validation

- Write one test for each rule of the validation, if each failure is a separate contract.
- Write one test with an empty payload to assert several required fields together.
- Give the status code in the name of a test for an API.
- Assert the text of the message that the user gets. A message that is present but wrong is a defect.
@if($pest)
- Use a dataset for input values that need the same setup and the same assertions.
@else
- Use a data provider with the `#[DataProvider]` attribute for input values that need the same setup and the same assertions. Use the `#[TestWith]` attribute for a small set of values.
@endif

Send an input value that is not valid through the application, and assert the error. Do not assert that an array of rules contains a string, because that assertion tests the declaration and not the behavior. Use such an assertion only for a rule that no request can reach, and write the reason in the test.

### Which Layer Owns Which Case

The test of a rule class owns the matrix of the values that pass and that fail. The test of the endpoint owns the proof that the endpoint applies the rule, and that the user gets the message.

Move the matrix to the test of the rule class when both tests hold it, and keep one case in the test of the endpoint. Never remove the last case, because the test of the rule class passes while the request forgets the rule. The same split applies to a policy, to a scope, and to any other class that a request calls.
