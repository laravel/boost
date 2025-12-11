<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Mcp\Prompts\BladePrompt;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    // Create a test blade file
    $this->testBladePath = sys_get_temp_dir().'/test-guideline.blade.php';
    file_put_contents($this->testBladePath, '# Test Guideline

This is a test guideline for testing.

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

test('it renders blade file as prompt', function (): void {
    $prompt = new BladePrompt('acme/payments', $this->testBladePath, $this->guidelineAssist);

    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Test Guideline')
        ->toolTextContains('Follow best practices')
        ->toolTextContains('Write clean code');
});

test('it generates correct prompt name from package', function (): void {
    $prompt = new BladePrompt('acme/payments', $this->testBladePath, $this->guidelineAssist);

    expect($prompt->name())->toBe('acme-payments-task');
});

test('it generates correct description from package', function (): void {
    $prompt = new BladePrompt('acme/payments', $this->testBladePath, $this->guidelineAssist);

    expect($prompt->description())->toBe('Guidelines for acme/payments');
});

test('it handles non-existent blade file gracefully', function (): void {
    $prompt = new BladePrompt('acme/test', '/non/existent/path.blade.php', $this->guidelineAssist);

    $response = $prompt->handle();

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

    $prompt = new BladePrompt('test/package', $this->testBladePath, $this->guidelineAssist);
    $response = $prompt->handle();

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

    $prompt = new BladePrompt('test/package', $this->testBladePath, $this->guidelineAssist);
    $response = $prompt->handle();

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

    $prompt = new BladePrompt('test/package', $this->testBladePath, $this->guidelineAssist);
    $response = $prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('<code-snippet name="example" lang="php">')
        ->toolTextContains('function example()');
});
