<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Resources\PackageGuidelineResource;

beforeEach(function (): void {
    $this->testBladePath = sys_get_temp_dir().'/test-resource-guideline.blade.php';
    file_put_contents($this->testBladePath, '# Test Resource Guideline

This is a test guideline for resource testing.

## Rules
- Follow best practices
- Write clean code');
});

afterEach(function (): void {
    if (file_exists($this->testBladePath)) {
        unlink($this->testBladePath);
    }
});

test('it generates resource uri from the package name', function (): void {
    $resource = new PackageGuidelineResource('acme/payments', $this->testBladePath);

    expect($resource->uri())->toBe('file://instructions/acme/payments.md');
});

test('it generates a description from the package name', function (): void {
    $resource = new PackageGuidelineResource('acme/payments', $this->testBladePath);

    expect($resource->description())->toBe('Guidelines for acme/payments');
});

test('it has Markdown mime type', function (): void {
    $resource = new PackageGuidelineResource('acme/payments', $this->testBladePath);

    expect($resource->mimeType())->toBe('text/markdown');
});

test('it renders blade guideline content', function (): void {
    $resource = new PackageGuidelineResource('acme/payments', $this->testBladePath);

    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Test Resource Guideline')
        ->toolTextContains('Follow best practices')
        ->toolTextContains('Write clean code');
});

test('it preserves inline code backticks in content', function (): void {
    $bladeContent = '# Guideline

Use `Model::factory()` to create models.

```php
User::factory()->create();
```';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new PackageGuidelineResource('test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('`Model::factory()`')
        ->toolTextContains('```php');
});

test('it preserves php tags in content', function (): void {
    $bladeContent = '# Guideline

Example code:

<?php
echo "Hello World";
?>';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new PackageGuidelineResource('test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<?php')
        ->toolTextContains('echo "Hello World"');
});

test('it processes boost snippet directives', function (): void {
    $bladeContent = '# Guideline

@boostsnippet(\'example\', \'php\')
function example() {
    return true;
}
@endboostsnippet';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new PackageGuidelineResource('test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<code-snippet name="example" lang="php">')
        ->toolTextContains('function example()');
});

test('it clears stored snippets between multiple handle calls', function (): void {
    $bladeContent = '# Guideline

@boostsnippet(\'example\', \'php\')
function example() {
    return true;
}
@endboostsnippet';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new PackageGuidelineResource('test/package', $this->testBladePath);

    $response1 = $resource->handle();
    $content1 = (string) $response1->content();

    $response2 = $resource->handle();
    $content2 = (string) $response2->content();

    expect($content1)->toBe($content2)
        ->and($content1)->toContain('<code-snippet name="example" lang="php">')
        ->and($content1)->not->toContain('___BOOST_SNIPPET___');
});

test('it handles non-existent blade file gracefully', function (): void {
    $resource = new PackageGuidelineResource('acme/test', '/non/existent/path.blade.php');

    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->and((string) $response->content())->toBe('');

});
