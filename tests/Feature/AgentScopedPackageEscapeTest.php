<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\Gemini;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\GuidelineWriter;

test('gemini agent escapes scoped npm packages in foundational context', function (): void {
    $factory = Mockery::mock(DetectionStrategyFactory::class);
    $gemini = new Gemini($factory);

    $markdown = <<<'MARKDOWN'
# Guidelines

## Foundational Context
This application uses:
- @inertiajs/react
- @tailwindcss/vite
- Some other package

## Other Section
- @inertiajs/react should not be escaped here.
MARKDOWN;

    $processed = $gemini->postProcessGuidelines($markdown);

    expect($processed)->toContain('\@inertiajs/react')
        ->and($processed)->toContain('\@tailwindcss/vite')
        ->and($processed)->toContain('## Other Section')
        ->and($processed)->not->toContain('\@inertiajs/react should not be escaped here.');
});

test('gemini agent does not double escape', function (): void {
    $factory = Mockery::mock(DetectionStrategyFactory::class);
    $gemini = new Gemini($factory);

    $markdown = <<<'MARKDOWN'
## Foundational Context
- \@inertiajs/react
MARKDOWN;

    $processed = $gemini->postProcessGuidelines($markdown);

    expect($processed)->toBe($markdown);
});

test('guideline writer applies post processing', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'boost_test_');

    $agent = Mockery::mock(Gemini::class);
    $agent->shouldReceive('guidelinesPath')->andReturn($tempFile);
    $agent->shouldReceive('postProcessGuidelines')->andReturn('processed guidelines');
    $agent->shouldReceive('frontmatter')->andReturn(false);

    $writer = new GuidelineWriter($agent);
    $writer->write('original guidelines');

    $content = file_get_contents($tempFile);
    expect($content)->toContain('processed guidelines')
        ->and($content)->not->toContain('original guidelines');

    unlink($tempFile);
});
