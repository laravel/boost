<?php

declare(strict_types=1);

use Laravel\Boost\Install\MarkdownFormatter;

it('leaves comment lines inside a fenced code block alone', function (): void {
    $content = <<<'MARKDOWN'
    ## Setup

    ```bash
    # Install dependencies
    composer install
    ```
    MARKDOWN;

    expect(MarkdownFormatter::format($content))->toBe($content);
});

it('leaves comment lines inside a tilde fenced code block alone', function (): void {
    $content = <<<'MARKDOWN'
    ~~~bash
    # Install dependencies
    composer install
    ~~~
    MARKDOWN;

    expect(MarkdownFormatter::format($content))->toBe($content);
});

it('keeps blank lines inside a fenced code block', function (): void {
    $content = <<<'MARKDOWN'
    ```php
    $first = 1;



    $second = 2;
    ```
    MARKDOWN;

    expect(MarkdownFormatter::format($content))->toBe($content);
});

it('only treats a hash at the start of a line as a heading', function (): void {
    $content = "Use # for comments in bash.\nNext line here.";

    expect(MarkdownFormatter::format($content))->toBe($content);
});

it('still adds a blank line before and after a heading', function (): void {
    expect(MarkdownFormatter::format("Some text\n## Heading\ntext after"))
        ->toBe("Some text\n\n## Heading\n\ntext after");
});

it('still adds a blank line between a heading and a following code fence', function (): void {
    expect(MarkdownFormatter::format("## Setup\n```bash\nls\n```"))
        ->toBe("## Setup\n\n```bash\nls\n```");
});

it('still collapses consecutive blank lines outside a fence', function (): void {
    expect(MarkdownFormatter::format("first\n\n\n\nsecond"))->toBe("first\n\nsecond");
});

it('still normalizes carriage returns', function (): void {
    expect(MarkdownFormatter::format("first\r\nsecond\rthird"))->toBe("first\nsecond\nthird");
});
