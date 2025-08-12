- Pest v4 is a huge upgrade offering: browser testing, smoke testing, visual regression testing, test sharding, faster type coverage, and profanity checking.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilising these features.

## Browser testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Test on multiple browsers (Chrome, Firefox, Safari).
- Test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode).
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.).
- Take screenshots or pause tests for debugging.

@verbatim
<code-snippet name="Pest browser test example" lang="php">
it('may reset the password', function () {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in') // visit on a real browser...
        ->on()->mobile() // or ->desktop(), ->tablet(), etc...
        ->inDarkMode(); // or ->inLightMode()

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>
@endverbatim
@verbatim
<code-snippet name="Pest smoke testing example" lang="php">
$pages = visit(['/', '/about', '/contact']);
$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>
@endverbatim
