@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Security Tests

Test each boundary where user input affects authorization, rendered output, or queries. Defects there are easy to miss because the feature keeps working.

- **Cross-tenant access.** Request another tenant's record; read `rules/endpoint-tests.md` on `404` vs `403`.
@if($pest)
- **Each unprivileged role.** Use a dataset over the roles the endpoint must refuse.
@else
- **Each unprivileged role.** Use a data provider over the roles the endpoint must refuse.
@endif
- **Escaping.** Cover HTML and mail, including names and every free-text field a template renders. Assert dangerous characters are escaped and the raw value is absent; do not assert an exact quote entity, since Markdown and mail CSS inliners may decode it.
- **Injection** into dynamic query parts such as sort columns, filter fields, and directions.
- **Unexpected keys** in payload or config arrays; a merge accepting every key can set attributes users must not control.

@if($pest)
```php
it('escapes dangerous content in the notification', function () {
    $organization = Organization::factory()->make([
        'name' => "O'Reilly <script>alert('xss')</script>",
    ]);

    $content = (new QuotaApproaching($organization, 80))->toMail()->render();

    expect($content)
        ->toContain('&lt;script&gt;')
        ->not->toContain("<script>alert('xss')</script>");
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

    $this->assertStringContainsString('&lt;script&gt;', $content);
    $this->assertStringNotContainsString("<script>alert('xss')</script>", $content);
}
```
@endif

Laravel provides the defenses; test that the app applies them to each attribute, route, and template.
