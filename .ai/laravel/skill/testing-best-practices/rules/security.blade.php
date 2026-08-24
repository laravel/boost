@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Security Tests

Write a test for the security boundary where an input value from a user reaches the authorization, the rendered output, or the construction of a query. A defect at such a boundary is difficult to find, because the feature continues to work.

Write a test for each case that follows:

- **The access across tenants.** Request a record of a different tenant, team, or organization. Read `rules/http-tests.md` for the reason to assert `404` and not `403`.
@if($pest)
- **Each role without the privilege.** Use a dataset over the roles that the endpoint must refuse.
@else
- **Each role without the privilege.** Use a data provider over the roles that the endpoint must refuse.
@endif
- **The escaping of the content of a user.** Test the escaping in HTML and in mail. Include a name and each free-text field that a template renders.
- **The injection into a dynamic part of a query.** Examples are a column to sort by, a field to filter by, and a direction of the order.
- **A key that you do not expect** in a payload or in a configuration array. A merge that accepts every key can set an attribute that the user must not control.

@if($pest)
```php
it('escapes dangerous content in the notification', function () {
    $organization = Organization::factory()->make([
        'name' => "O'Reilly <script>alert('xss')</script>",
    ]);

    $content = (new QuotaApproaching($organization, 80))->toMail()->render();

    expect($content)->not->toContain("<script>alert('xss')</script>");
});
```
@else
```php
public function test_escapes_dangerous_content_in_the_notification(): void
{
    $organization = Organization::factory()->make([
        'name' => "O'Reilly <script>alert('xss')</script>",
    ]);

    $content = (new QuotaApproaching($organization, 80))->toMail()->render();

    $this->assertStringNotContainsString("<script>alert('xss')</script>", $content);
}
```
@endif

Laravel gives a defense for mass assignment, for authorization, and for escaping. Test that this application uses the defense, because the application must select the defense for each attribute, each route, and each template.
