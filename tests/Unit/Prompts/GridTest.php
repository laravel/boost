<?php

declare(strict_types=1);

use Laravel\Boost\Prompts\Grid;
use Laravel\Prompts\Prompt;

it('renders a grid with multiple items from arrays', function ($items): void {
    Prompt::fake();

    (new Grid($items, maxWidth: 80))->display();

    Prompt::assertStrippedOutputContains('pest');
    Prompt::assertStrippedOutputContains('phpstan');
    Prompt::assertStrippedOutputContains('pint');
    Prompt::assertStrippedOutputContains('rector');
})->with([
    'arrays' => [['pest', 'phpstan', 'pint', 'rector']],
    'collections' => [collect(['pest', 'phpstan', 'pint', 'rector'])],
]);

it('renders a grid with a single item', function ($items): void {
    Prompt::fake();

    (new Grid($items, maxWidth: 80))->display();

    Prompt::assertStrippedOutputContains('laravel');
})->with([
    'arrays' => [['laravel']],
    'collections' => [collect(['laravel'])],
]);

it('renders an empty grid without any output', function ($items): void {
    Prompt::fake();

    (new Grid($items, maxWidth: 80))->display();

    expect(Prompt::content())->toBe('');
})->with([
    'arrays' => [[]],
    'collections' => [collect()],
]);

it('renders a grid containing unicode characters', function ($items): void {
    Prompt::fake();

    (new Grid($items, maxWidth: 80))->display();

    Prompt::assertStrippedOutputContains('測試');
    Prompt::assertStrippedOutputContains('café');
    Prompt::assertStrippedOutputContains('🚀');
})->with([
    'arrays' => [['測試', 'café', '🚀']],
    'collections' => [collect(['測試', 'café', '🚀'])],
]);

it('renders box drawing characters for grid borders', function (): void {
    Prompt::fake();

    (new Grid(['item1', 'item2'], maxWidth: 80))->display();

    $output = Prompt::content();

    expect($output)->toContain('┌')
        ->and($output)->toContain('┐')
        ->and($output)->toContain('└')
        ->and($output)->toContain('┘')
        ->and($output)->toContain('│')
        ->and($output)->toContain('─');
});

it('renders table separators between multiple rows', function (): void {
    Prompt::fake();

    (new Grid(['item1', 'item2', 'item3', 'item4', 'item5', 'item6', 'item7', 'item8', 'item9', 'item10'], maxWidth: 50))->display();

    $output = Prompt::content();

    expect($output)->toContain('├')
        ->and($output)->toContain('┤');
});

it('respects the custom maxWidth parameter', function (): void {
    Prompt::fake();

    (new Grid(['item1', 'item2', 'item3'], maxWidth: 40))->display();

    $output = Prompt::content();

    expect($output)->toContain('item1')
        ->and($output)->toContain('item2')
        ->and($output)->toContain('item3');
});

it('uses default terminal width when maxWidth is not provided', function (): void {
    Prompt::fake();

    (new Grid(['item1', 'item2']))->display();

    $output = Prompt::content();

    expect($output)->toContain('item1')
        ->and($output)->toContain('item2');
});

it('handles grid items with varying character lengths', function (): void {
    Prompt::fake();

    (new Grid(['a', 'medium-length-item', 'xyz'], maxWidth: 80))->display();

    Prompt::assertStrippedOutputContains('a');
    Prompt::assertStrippedOutputContains('medium-length-item');
    Prompt::assertStrippedOutputContains('xyz');
});

it('arranges many items in balanced columns across multiple rows', function (): void {
    Prompt::fake();

    $items = ['item1', 'item2', 'item3', 'item4', 'item5', 'item6', 'item7', 'item8', 'item9'];

    (new Grid($items, maxWidth: 80))->display();

    foreach ($items as $item) {
        Prompt::assertStrippedOutputContains($item);
    }
});

it('renders grid items containing special characters', function (): void {
    Prompt::fake();

    (new Grid(['@laravel', '#boost', '$100', '%progress'], maxWidth: 80))->display();

    Prompt::assertStrippedOutputContains('@laravel');
    Prompt::assertStrippedOutputContains('#boost');
    Prompt::assertStrippedOutputContains('$100');
    Prompt::assertStrippedOutputContains('%progress');
});

it('pads incomplete rows with empty cells to maintain grid structure', function (): void {
    Prompt::fake();

    (new Grid(['item1', 'item2', 'item3', 'item4', 'item5'], maxWidth: 80))->display();

    $output = Prompt::content();

    expect($output)->toContain('item1')
        ->and($output)->toContain('item2')
        ->and($output)->toContain('item3')
        ->and($output)->toContain('item4')
        ->and($output)->toContain('item5')
        ->and($output)->toContain('│');
});

it('returns true when the prompt method is called', function (): void {
    Prompt::fake();

    $grid = new Grid(['item1', 'item2'], maxWidth: 80);

    expect($grid->prompt())->toBeTrue();
});

it('returns true when the value method is called', function (): void {
    Prompt::fake();

    $grid = new Grid(['item1', 'item2'], maxWidth: 80);

    expect($grid->value())->toBeTrue();
});

it('sets the prompt state to submit after rendering', function (): void {
    Prompt::fake();

    $grid = new Grid(['item1'], maxWidth: 80);
    $grid->prompt();

    expect($grid->state)->toBe('submit');
});

it('displays grid items when the display method is called', function (): void {
    Prompt::fake();

    $grid = new Grid(['item1', 'item2'], maxWidth: 80);
    $grid->display();

    Prompt::assertStrippedOutputContains('item1');
    Prompt::assertStrippedOutputContains('item2');
});

it('does not displays grid items when the display method is called', function (): void {
    Prompt::fake();

    new Grid(['item1', 'item2'], maxWidth: 80);

    Prompt::assertStrippedOutputDoesntContain('item1');
});

it('renders a complete grid with multiple rows and balanced columns', function (): void {
    Prompt::fake();

    (new Grid([
        'building-livewire-components',
        'building-mcp-servers',
        'testing-with-pest',
        'using-fluxui',
        'using-folio-routing',
        'using-tailwindcss',
    ], maxWidth: 120))->display();

    Prompt::assertStrippedOutputContains(<<<'OUTPUT'
         ┌──────────────────────────────┬──────────────────────┬───────────────────┐
         │ building-livewire-components │ building-mcp-servers │ testing-with-pest │
         ├──────────────────────────────┼──────────────────────┼───────────────────┤
         │ using-fluxui                 │ using-folio-routing  │ using-tailwindcss │
         └──────────────────────────────┴──────────────────────┴───────────────────┘
        OUTPUT);
});
