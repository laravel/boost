@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# HTTP and Authorization Tests

## The Coverage of an Endpoint

Write a test for each case that follows, if the case applies to the endpoint:

- The request has no authentication, or the authentication is not valid.
- The request comes from a different tenant, team, or organization.
- The user has an insufficient role or an insufficient permission.
- The request does not meet a constraint of the route or of the scope.
- The request fails the validation.
- The request is correct. Assert the response and the state that the request saves.

Assert the actual behavior of the application, and not a general status code. An API returns `401` for a token that is absent or not valid. A browser endpoint redirects the user to the sign-in route.

## The Isolation of a Tenant

Return `404` if the existence of a record is information that one tenant must not get about a different tenant. Assert `404` in the test. Do not assert `403`.

@if($pest)
```php
it('returns 404 when the application belongs to another organization', function () {
    $application = Application::factory()->create();

    $this->withOrgToken(Organization::factory()->create())
        ->get(route('api.applications.show', $application))
        ->assertNotFound();
});
```
@else
```php
public function test_returns_404_when_the_application_belongs_to_another_organization(): void
{
    $application = Application::factory()->create();

    $this->withOrgToken(Organization::factory()->create())
        ->get(route('api.applications.show', $application))
        ->assertNotFound();
}
```
@endif

## Test the Authorization at the Policy

An HTTP test shows that the endpoint calls the authorization. An HTTP test cannot show which mechanism refused the request, because middleware, a policy, or a call to `abort()` can each return `403`. Apply the three rules that follow:

- Assert the complete matrix of the permissions against the policy or the gate. A failure then names the rule that is not correct.
- Write one HTTP test for one refused role. This test shows that the endpoint calls the authorization.
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

```php
$response->assertUnprocessable()
    ->assertJsonValidationErrors(['cluster_id'])
    ->assertJsonPath('errors.cluster_id.0', 'The selected compute is not available.');
```

Send an input value that is not valid through the application, and assert the error. Do not assert that an array of rules contains a string, because that assertion tests the declaration and not the behavior. Use such an assertion only for a rule that no request can reach, and write the reason in the test.
