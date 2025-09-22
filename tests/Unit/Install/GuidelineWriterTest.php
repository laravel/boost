<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\GuidelineWriter;

test('it returns NOOP when guidelines are empty', function (): void {
    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn('/tmp/test.md');

    $writer = new GuidelineWriter($agent);

    $result = $writer->write('');
    expect($result)->toBe(GuidelineWriter::NOOP);
});

test('it creates directory when it does not exist', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $filePath = $tempDir.'/subdir/test.md';

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($filePath);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('test guidelines');

    expect(is_dir(dirname($filePath)))->toBeTrue()
        ->and(file_exists($filePath))->toBeTrue();

    // Cleanup
    unlink($filePath);
    rmdir(dirname($filePath));
    rmdir($tempDir);
});

test('it throws exception when directory creation fails', function (): void {
    // Use a path that cannot be created (root directory with insufficient permissions)
    $filePath = '/root/boost_test/test.md';

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($filePath);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);

    expect(fn (): int => $writer->write('test guidelines'))
        ->toThrow(RuntimeException::class, 'Failed to create directory: /root/boost_test');
})->skipOnWindows();

test('it writes guidelines to new file', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('test guidelines content');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("<laravel-boost-guidelines>\ntest guidelines content\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it writes guidelines to existing file without existing guidelines', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, "# Existing content\n\nSome text here.");

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('new guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("# Existing content\n\nSome text here.\n\n===\n\n<laravel-boost-guidelines>\nnew guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it replaces existing guidelines in-place', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "# Header\n\n<laravel-boost-guidelines>\nold guidelines\n</laravel-boost-guidelines>\n\n# Footer";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('updated guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("# Header\n\n<laravel-boost-guidelines>\nupdated guidelines\n</laravel-boost-guidelines>\n\n# Footer");

    unlink($tempFile);
});

test('it handles multiline existing guidelines', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "Start\n<laravel-boost-guidelines>\nline 1\nline 2\nline 3\n</laravel-boost-guidelines>\nEnd";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('single line');

    $content = file_get_contents($tempFile);
    // Should replace in-place, preserving structure
    expect($content)->toBe("Start\n<laravel-boost-guidelines>\nsingle line\n</laravel-boost-guidelines>\nEnd");

    unlink($tempFile);
});

test('it handles multiple guideline blocks', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "Start\n<laravel-boost-guidelines>\nfirst\n</laravel-boost-guidelines>\nMiddle\n<laravel-boost-guidelines>\nsecond\n</laravel-boost-guidelines>\nEnd";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('replacement');

    $content = file_get_contents($tempFile);
    // Should replace first occurrence, second block remains untouched due to non-greedy matching
    expect($content)->toBe("Start\n<laravel-boost-guidelines>\nreplacement\n</laravel-boost-guidelines>\nMiddle\n<laravel-boost-guidelines>\nsecond\n</laravel-boost-guidelines>\nEnd");

    unlink($tempFile);
});

test('it throws exception when file cannot be opened', function (): void {
    // Use a directory path instead of file path to cause fopen to fail
    $dirPath = sys_get_temp_dir();

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($dirPath);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);

    expect(fn (): int => $writer->write('test guidelines'))
        ->toThrow(RuntimeException::class, "Failed to open file: {$dirPath}");
})->skipOnWindows();

test('it preserves file content structure with proper spacing', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "# Title\n\nParagraph 1\n\nParagraph 2";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('my guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("# Title\n\nParagraph 1\n\nParagraph 2\n\n===\n\n<laravel-boost-guidelines>\nmy guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it handles empty file', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, '');

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('first guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("<laravel-boost-guidelines>\nfirst guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it handles file with only whitespace', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, "   \n\n  \t  \n");

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('clean guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("<laravel-boost-guidelines>\nclean guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it does not interfere with other XML-like tags', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "# Title\n\n<other-rules>\nShould not be touched\n</other-rules>\n\n<laravel-boost-guidelines>\nOld guidelines\n</laravel-boost-guidelines>\n\n<custom-config>\nAlso untouched\n</custom-config>";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $result = $writer->write('new guidelines');

    expect($result)->toBe(GuidelineWriter::REPLACED);
    $content = file_get_contents($tempFile);
    expect($content)->toBe("# Title\n\n<other-rules>\nShould not be touched\n</other-rules>\n\n<laravel-boost-guidelines>\nnew guidelines\n</laravel-boost-guidelines>\n\n<custom-config>\nAlso untouched\n</custom-config>");

    unlink($tempFile);
});

test('it preserves user content after guidelines when replacing', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    $initialContent = "# My Project\n\n<laravel-boost-guidelines>\nold guidelines\n</laravel-boost-guidelines>\n\n# User Added Section\nThis content was added by the user after the guidelines.\n\n## Another user section\nMore content here.";
    file_put_contents($tempFile, $initialContent);

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('updated guidelines from boost');

    $content = file_get_contents($tempFile);

    // Verify guidelines were replaced in-place
    expect($content)->toContain('<laravel-boost-guidelines>')
        ->and($content)->toContain('updated guidelines from boost');

    // Verify user content after guidelines is preserved
    expect($content)->toContain('# User Added Section')
        ->and($content)->toContain('This content was added by the user after the guidelines.')
        ->and($content)->toContain('## Another user section')
        ->and($content)->toContain('More content here.');

    // Verify exact structure
    expect($content)->toBe("# My Project\n\n<laravel-boost-guidelines>\nupdated guidelines from boost\n</laravel-boost-guidelines>\n\n# User Added Section\nThis content was added by the user after the guidelines.\n\n## Another user section\nMore content here.");

    unlink($tempFile);
});

test('it retries file locking on contention', function (): void {
    expect(true)->toBeTrue(); // Mark as passing for now
})->todo();

test('it adds frontmatter when agent supports it and file has no existing frontmatter', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, "# Existing content\n\nSome text here.");

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(true);

    $writer = new GuidelineWriter($agent);
    $writer->write('new guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("---\nalwaysApply: true\n---\n# Existing content\n\nSome text here.\n\n===\n\n<laravel-boost-guidelines>\nnew guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it does not add frontmatter when agent supports it but file already has frontmatter', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, "---\ncustomOption: true\n---\n# Existing content\n\nSome text here.");

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(true);

    $writer = new GuidelineWriter($agent);
    $writer->write('new guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toBe("---\ncustomOption: true\n---\n# Existing content\n\nSome text here.\n\n===\n\n<laravel-boost-guidelines>\nnew guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});

test('it does not add frontmatter when agent does not support it', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');
    file_put_contents($tempFile, "# Existing content\n\nSome text here.");

    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $result = $writer->write('new guidelines');

    expect($result)->toBe(GuidelineWriter::NEW);
    $content = file_get_contents($tempFile);
    expect($content)->toBe("# Existing content\n\nSome text here.\n\n===\n\n<laravel-boost-guidelines>\nnew guidelines\n</laravel-boost-guidelines>");

    unlink($tempFile);
});
