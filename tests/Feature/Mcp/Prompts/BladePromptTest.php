<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\PackageGuidelinePrompt;

beforeEach(function (): void {
    $this->testBladePath = sys_get_temp_dir().'/test-guideline.blade.php';
    file_put_contents($this->testBladePath, '# Test Guideline

    This is a test guideline for testing.

    ## Rules
    - Follow best practices
    - Write clean code');
});

afterEach(function (): void {
    if (file_exists($this->testBladePath)) {
        unlink($this->testBladePath);
    }
});

it('renders a blade file as a prompt', function (): void {
    $prompt = new PackageGuidelinePrompt('acme/payments', $this->testBladePath);

    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Test Guideline')
        ->toolTextContains('Follow best practices')
        ->toolTextContains('Write clean code');
});

it('generates correct metadata from the package name', function (): void {
    $prompt = new PackageGuidelinePrompt('acme/payments', $this->testBladePath);

    expect($prompt->name())->toBe('acme/payments')
        ->and($prompt->description())->toBe('Guidelines for acme/payments');
});

it('handles a non-existent blade file gracefully', function (): void {
    $prompt = new PackageGuidelinePrompt('acme/test', '/non/existent/path.blade.php');

    $response = $prompt->handle();

    expect($response)->isToolResult()->toolHasNoError()
        ->and((string) $response->content())->toBe('');

});

it('processes backticks in blade content', function (): void {
    $bladeContent = '# Guideline

Use `Model::factory()` to create models.

```php
User::factory()->create();
```';

    file_put_contents($this->testBladePath, $bladeContent);

    $prompt = new PackageGuidelinePrompt('test/package', $this->testBladePath);
    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('`Model::factory()`')
        ->toolTextContains('```php');
});

it('processes php tags in blade content', function (): void {
    $bladeContent = '# Guideline

Example code:

<?php
echo "Hello World";
?>';

    file_put_contents($this->testBladePath, $bladeContent);

    $prompt = new PackageGuidelinePrompt('test/package', $this->testBladePath);
    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<?php')
        ->toolTextContains('echo "Hello World"');
});

it('processes boost snippets', function (): void {
    $bladeContent = '# Guideline

@boostsnippet(\'example\', \'php\')
function example() {
    return true;
}
@endboostsnippet';

    file_put_contents($this->testBladePath, $bladeContent);

    $prompt = new PackageGuidelinePrompt('test/package', $this->testBladePath);
    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<code-snippet name="example" lang="php">')
        ->toolTextContains('function example()');
});

it('clears stored snippets between multiple handle calls', function (): void {
    $bladeContent = '# Guideline

@boostsnippet(\'example\', \'php\')
function example() {
    return true;
}
@endboostsnippet';

    file_put_contents($this->testBladePath, $bladeContent);

    $prompt = new PackageGuidelinePrompt('test/package', $this->testBladePath);

    $response1 = $prompt->handle();
    $content1 = (string) $response1->content();

    $response2 = $prompt->handle();
    $content2 = (string) $response2->content();

    expect($content1)->toBe($content2)
        ->and($content1)->toContain('<code-snippet name="example" lang="php">')
        ->and($content1)->not->toContain('___BOOST_SNIPPET_');
});
