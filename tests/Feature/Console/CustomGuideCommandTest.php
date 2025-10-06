<?php

declare(strict_types=1);

beforeEach(function (): void {
    // Clean up any test files created
    $this->testDirectory = base_path('.ai/guidelines');
});

afterEach(function (): void {
    // Clean up test files
    if (is_dir($this->testDirectory)) {
        $files = glob($this->testDirectory.'/*.blade.php');
        foreach ($files as $file) {
            if (str_contains(basename($file), 'test-') ||
                str_contains(basename($file), 'my-') ||
                str_contains(basename($file), 'myguidewithspecial')) {
                @unlink($file);
            }
        }
    }
});

test('creates a custom guide successfully', function (): void {
    $this->artisan('boost:custom', ['name' => 'test-success'])
        ->expectsQuestion('* What is the title of your custom guide?', 'My Custom Guide')
        ->expectsQuestion('* Describe the purpose of this guide (optional)', 'This is a test guide')
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/test-success.blade.php');
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toContain('# My Custom Guide');
    expect($content)->toContain('## This is a test guide');
    expect($content)->toContain('<!-- Add your custom guidelines here -->');
});

test('shows error for invalid guide name with special characters', function (): void {
    $this->artisan('boost:custom', ['name' => '!!!@@@###'])
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/.blade.php');
    expect(file_exists($filePath))->toBeFalse();
});

test('shows error when guide already exists', function (): void {
    // Create the file first
    $directory = base_path('.ai/guidelines');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $filePath = $directory.'/test-existing.blade.php';
    file_put_contents($filePath, '# Existing guide');

    // Try to create it again
    $this->artisan('boost:custom', ['name' => 'test-existing'])
        ->assertExitCode(0);

    // Content should not change
    $content = file_get_contents($filePath);
    expect($content)->toBe('# Existing guide');

    // Clean up
    @unlink($filePath);
});

test('sanitizes guide name correctly', function (): void {
    $this->artisan('boost:custom', ['name' => 'my-guide-123_test'])
        ->expectsQuestion('* What is the title of your custom guide?', 'Test Guide')
        ->expectsQuestion('* Describe the purpose of this guide (optional)', 'Description')
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/my-guide-123_test.blade.php');
    expect(file_exists($filePath))->toBeTrue();
});

test('removes special characters from guide name', function (): void {
    $this->artisan('boost:custom', ['name' => 'my@guide#with$special'])
        ->expectsQuestion('* What is the title of your custom guide?', 'Clean Guide')
        ->expectsQuestion('* Describe the purpose of this guide (optional)', 'Description')
        ->assertExitCode(0);

    // Should create file without special characters
    $filePath = base_path('.ai/guidelines/myguidewithspecial.blade.php');
    expect(file_exists($filePath))->toBeTrue();
});

test('shows error when name becomes empty after sanitization', function (): void {
    $this->artisan('boost:custom', ['name' => '@@@###!!!'])
        ->assertExitCode(0);

    // No file should be created
    $directory = base_path('.ai/guidelines');
    if (is_dir($directory)) {
        $files = glob($directory.'/.blade.php');
        expect($files)->toBeEmpty();
    }
});

test('creates guide without description when description is empty', function (): void {
    $this->artisan('boost:custom', ['name' => 'test-nodesc'])
        ->expectsQuestion('* What is the title of your custom guide?', 'My Guide')
        ->expectsQuestion('* Describe the purpose of this guide (optional)', '')
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/test-nodesc.blade.php');
    $content = file_get_contents($filePath);

    expect($content)->toContain('# My Guide');
    expect($content)->toContain('<!-- Add your custom guidelines here -->');
    expect($content)->toContain('## Project Overview');
    // Verify no user description is added before the template sections
    $lines = explode("\n", $content);
    $titleIndex = array_search('# My Guide', $lines);
    $commentIndex = array_search('<!-- Add your custom guidelines here -->', $lines);
    // Between title and comment, there should only be blank lines (no ## description)
    for ($i = $titleIndex + 1; $i < $commentIndex; $i++) {
        expect(trim($lines[$i]))->toBe('');
    }
});

test('truncates title to 200 characters', function (): void {
    $longTitle = str_repeat('a', 250);

    $this->artisan('boost:custom', ['name' => 'test-longtitle'])
        ->expectsQuestion('* What is the title of your custom guide?', $longTitle)
        ->expectsQuestion('* Describe the purpose of this guide (optional)', 'Description')
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/test-longtitle.blade.php');
    $content = file_get_contents($filePath);

    $truncatedTitle = str_repeat('a', 200);
    expect($content)->toContain("# {$truncatedTitle}");

    // Verify it's actually truncated and not longer
    $firstLine = explode("\n", $content)[0];
    expect(strlen($firstLine))->toBeLessThanOrEqual(202); // "# " + 200 chars
});

test('truncates description to 500 characters', function (): void {
    $longDescription = str_repeat('b', 600);

    $this->artisan('boost:custom', ['name' => 'test-longdesc'])
        ->expectsQuestion('* What is the title of your custom guide?', 'Title')
        ->expectsQuestion('* Describe the purpose of this guide (optional)', $longDescription)
        ->assertExitCode(0);

    $filePath = base_path('.ai/guidelines/test-longdesc.blade.php');
    $content = file_get_contents($filePath);

    $truncatedDescription = str_repeat('b', 500);
    expect($content)->toContain("## {$truncatedDescription}");

    // Find the description line and verify it's truncated
    $lines = explode("\n", $content);
    $descLine = array_filter($lines, fn ($line) => str_starts_with($line, '##'));
    $descLine = reset($descLine);
    expect(strlen($descLine))->toBeLessThanOrEqual(503); // "## " + 500 chars
});
