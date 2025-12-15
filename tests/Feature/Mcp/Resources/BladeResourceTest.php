<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Mcp\Resources\ThirdPartyResource;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->testBladePath = sys_get_temp_dir().'/test-resource-guideline.blade.php';
    file_put_contents($this->testBladePath, '# Test Resource Guideline

This is a test guideline for resource testing.

## Rules
- Follow best practices
- Write clean code');

    $roster = app(Roster::class);
    $herd = app(Herd::class);
    $this->guidelineAssist = new GuidelineAssist($roster, new GuidelineConfig, $herd);
});

afterEach(function (): void {
    if (file_exists($this->testBladePath)) {
        unlink($this->testBladePath);
    }
});

test('it renders blade file as resource', function (): void {
    $resource = new ThirdPartyResource($this->guidelineAssist, 'acme/payments', $this->testBladePath);

    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Test Resource Guideline')
        ->toolTextContains('Follow best practices')
        ->toolTextContains('Write clean code');
});

test('it generates correct resource uri from package', function (): void {
    $resource = new ThirdPartyResource($this->guidelineAssist, 'acme/payments', $this->testBladePath);

    expect($resource->uri())->toBe('file://instructions/acme/payments.md');
});

test('it generates correct description from package', function (): void {
    $resource = new ThirdPartyResource($this->guidelineAssist, 'acme/payments', $this->testBladePath);

    expect($resource->description())->toBe('Guidelines for acme/payments');
});

test('it has correct mime type', function (): void {
    $resource = new ThirdPartyResource($this->guidelineAssist, 'acme/payments', $this->testBladePath);

    expect($resource->mimeType())->toBe('text/markdown');
});

test('it handles non-existent blade file gracefully', function (): void {
    $resource = new ThirdPartyResource($this->guidelineAssist, 'acme/test', '/non/existent/path.blade.php');

    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError();

    expect((string) $response->content())->toBe('');
});

test('it processes backticks in blade content', function (): void {
    $bladeContent = '# Guideline

Use `Model::factory()` to create models.

```php
User::factory()->create();
```';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new ThirdPartyResource($this->guidelineAssist, 'test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('`Model::factory()`')
        ->toolTextContains('```php');
});

test('it processes php tags in blade content', function (): void {
    $bladeContent = '# Guideline

Example code:

<?php
echo "Hello World";
?>';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new ThirdPartyResource($this->guidelineAssist, 'test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<?php')
        ->toolTextContains('echo "Hello World"');
});

test('it processes boost snippets', function (): void {
    $bladeContent = '# Guideline

@boostsnippet(\'example\', \'php\')
function example() {
    return true;
}
@endboostsnippet';

    file_put_contents($this->testBladePath, $bladeContent);

    $resource = new ThirdPartyResource($this->guidelineAssist, 'test/package', $this->testBladePath);
    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<code-snippet name="example" lang="php">')
        ->toolTextContains('function example()');
});
