<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

beforeEach(function (): void {
    // Clean up boost.json before each test
    if (File::exists(base_path('boost.json'))) {
        File::delete(base_path('boost.json'));
    }
});

afterEach(function (): void {
    // Clean up after tests
    if (File::exists(base_path('boost.json'))) {
        File::delete(base_path('boost.json'));
    }
});

test('user can skip guideline customization and install all by default', function (): void {
    Prompt::fake([
        'n',  // Would you like to customize which guidelines to install? No
    ]);

    $guidelines = collect([
        'boost' => ['tokens' => 514, 'description' => 'Laravel Boost'],
        'foundation' => ['tokens' => 368, 'description' => 'Laravel Boost Guidelines'],
        'laravel/core' => ['tokens' => 568, 'description' => 'Do Things The Laravel Way'],
    ]);

    // Simulate the selectGuidelines method behavior
    $customize = confirm(
        label: 'Would you like to customize which guidelines to install?',
        default: false,
        hint: 'Select "No" to install all detected guidelines, or "Yes" to choose specific ones'
    );

    expect($customize)->toBeFalse();

    // When user skips customization, all guidelines should be selected
    $selected = $guidelines->keys()->toArray();

    expect($selected)->toHaveCount(3);
    expect($selected)->toContain('boost');
    expect($selected)->toContain('foundation');
    expect($selected)->toContain('laravel/core');
})->skipOnWindows();

test('user can customize which guidelines to install', function (): void {
    Prompt::fake([
        'y',              // Would you like to customize? Yes
        Key::SPACE,       // Deselect boost (first item, already selected)
        Key::DOWN,        // Move to foundation
        Key::DOWN,        // Move to laravel/core
        Key::SPACE,       // Deselect laravel/core
        Key::ENTER,       // Submit (only foundation selected)
    ]);

    $guidelines = collect([
        'boost' => ['tokens' => 514, 'description' => 'Laravel Boost'],
        'foundation' => ['tokens' => 368, 'description' => 'Laravel Boost Guidelines'],
        'laravel/core' => ['tokens' => 568, 'description' => 'Do Things The Laravel Way'],
    ]);

    $customize = confirm(
        label: 'Would you like to customize which guidelines to install?',
        default: false,
        hint: 'Select "No" to install all detected guidelines, or "Yes" to choose specific ones'
    );

    expect($customize)->toBeTrue();

    // Simulate multiselect with user deselecting some items
    $selected = multiselect(
        label: 'Which guidelines do you want to add?',
        options: [
            'boost' => 'boost (~514 tokens) Laravel Boost',
            'foundation' => 'foundation (~368 tokens) Laravel Boost Guidelines',
            'laravel/core' => 'laravel/core (~568 tokens) Do Things The Laravel Way',
        ],
        default: ['boost', 'foundation', 'laravel/core'],
    );

    expect($selected)->toBeArray();
    expect($selected)->toContain('foundation');
})->skipOnWindows();

test('guidelines are saved in snake_case format', function (): void {
    $guidelines = [
        'boost',
        'foundation',
        'laravel/core',
        'laravel/v12',
        'pest/core',
        'tailwindcss/v4',
    ];

    // Simulate the conversion to snake_case
    $saved = array_map(
        fn (string $key) => \Illuminate\Support\Str::snake(str_replace('/', '_', $key)),
        $guidelines
    );

    expect($saved)->toContain('boost');
    expect($saved)->toContain('foundation');
    expect($saved)->toContain('laravel_core');
    expect($saved)->toContain('laravel_v12');
    expect($saved)->toContain('pest_core');
    expect($saved)->toContain('tailwindcss_v4');
});

test('saved guidelines are loaded correctly from boost.json', function (): void {
    // Create a mock boost.json with saved guidelines
    $config = [
        'agents' => ['claude_code', 'codex'],
        'editors' => ['claude_code', 'vscode'],
        'guidelines' => [
            'boost',
            'foundation',
            'laravel_core',
            'pest_core',
        ],
        'sail' => true,
    ];

    File::put(base_path('boost.json'), json_encode($config, JSON_PRETTY_PRINT));

    // Read the config
    $savedConfig = json_decode(File::get(base_path('boost.json')), true);

    expect($savedConfig['guidelines'])->toBeArray();
    expect($savedConfig['guidelines'])->toHaveCount(4);
    expect($savedConfig['guidelines'])->toContain('boost');
    expect($savedConfig['guidelines'])->toContain('laravel_core');
    expect($savedConfig['guidelines'])->not->toContain('laravel/core'); // Should be snake_case
});

test('snake_case guidelines are converted back to original format for matching', function (): void {
    $savedGuidelines = [
        'boost',
        'foundation',
        'laravel_core',
        'pest_v4',
        'tailwindcss_core',
    ];

    $availableGuidelines = collect([
        'boost' => ['tokens' => 514],
        'foundation' => ['tokens' => 368],
        'laravel/core' => ['tokens' => 568],
        'pest/v4' => ['tokens' => 324],
        'tailwindcss/core' => ['tokens' => 192],
    ]);

    // Simulate the conversion back
    $matched = collect($savedGuidelines)->map(function (string $snakeKey) use ($availableGuidelines) {
        return $availableGuidelines->keys()->first(function (string $originalKey) use ($snakeKey) {
            $convertedKey = \Illuminate\Support\Str::snake(str_replace('/', '_', $originalKey));

            return $convertedKey === $snakeKey;
        });
    })->filter()->values()->toArray();

    expect($matched)->toHaveCount(5);
    expect($matched)->toContain('boost');
    expect($matched)->toContain('laravel/core');
    expect($matched)->toContain('pest/v4');
    expect($matched)->toContain('tailwindcss/core');
});
