@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# HTTP and Authorization Tests

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
