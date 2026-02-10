<?php

declare(strict_types=1);

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\GuidelineAssist;

beforeEach(function (): void {
    $this->renderer = new class
    {
        use RendersBladeGuidelines;

        public function processSnippets(string $content): string
        {
            return $this->processBoostSnippets($content);
        }

        public function render(string $content, string $path): string
        {
            return $this->renderContent($content, $path);
        }

        public function renderFile(string $bladePath): string
        {
            return $this->renderBladeFile($bladePath);
        }

        public function getStoredSnippets(): array
        {
            return $this->storedSnippets;
        }
    };
});

test('boostsnippet directive extracts name and content into fenced code block', function (): void {
    $content = "@boostsnippet('Authentication Example')return Auth::user();@endboostsnippet";

    $result = $this->renderer->processSnippets($content);

    expect($result)->toBe('___BOOST_SNIPPET_0___');

    $snippet = $this->renderer->getStoredSnippets()['___BOOST_SNIPPET_0___'];
    expect($snippet)
        ->toStartWith('<!-- Authentication Example -->')
        ->toContain('```html')
        ->toContain('return Auth::user();')
        ->toContain('```');
});

test('boostsnippet supports double quotes for name parameter', function (): void {
    $content = '@boostsnippet("Double Quoted")code@endboostsnippet';

    $this->renderer->processSnippets($content);

    expect($this->renderer->getStoredSnippets()['___BOOST_SNIPPET_0___'])
        ->toStartWith('<!-- Double Quoted -->');
});

test('boostsnippet uses specified language in fenced code block', function (): void {
    $content = "@boostsnippet('PHP Example', 'php')\$user = User::find(1);@endboostsnippet";

    $this->renderer->processSnippets($content);

    expect($this->renderer->getStoredSnippets()['___BOOST_SNIPPET_0___'])
        ->toContain('```php')
        ->toContain('$user = User::find(1);');
});

test('multiple boostsnippets are replaced with sequential placeholders', function (): void {
    $content = "@boostsnippet('First')code1@endboostsnippet between @boostsnippet('Second', 'js')code2@endboostsnippet";

    $result = $this->renderer->processSnippets($content);

    expect($result)->toBe('___BOOST_SNIPPET_0___ between ___BOOST_SNIPPET_1___')
        ->and($this->renderer->getStoredSnippets())->toHaveCount(2)
        ->and($this->renderer->getStoredSnippets()['___BOOST_SNIPPET_1___'])->toContain('```js');
});

test('escaped boostsnippet directive is not processed', function (): void {
    $content = "@@boostsnippet('Escaped')content@@endboostsnippet";

    $result = $this->renderer->processSnippets($content);

    expect($result)->toBe($content)
        ->and($this->renderer->getStoredSnippets())->toBeEmpty();
});

test('boostsnippet preserves multiline content', function (): void {
    $content = "@boostsnippet('Multiline')\$user = User::find(1);\n\$user->name = 'John';\n\$user->save();@endboostsnippet";

    $this->renderer->processSnippets($content);

    expect($this->renderer->getStoredSnippets()['___BOOST_SNIPPET_0___'])
        ->toContain("\$user = User::find(1);\n\$user->name = 'John';\n\$user->save();");
});

test('non-blade files bypass blade rendering entirely', function (): void {
    $bladeContent = '{{ $variable }} @if(true) test @endif';

    $result = $this->renderer->render($bladeContent, '/path/to/readme.md');

    expect($result)->toBe($bladeContent);
});

test('backticks are preserved through blade rendering for inline code documentation', function (): void {
    $this->mock(GuidelineAssist::class);

    $content = 'Run `composer install` then `php artisan migrate`';

    $result = $this->renderer->render($content, '/path/to/guide.blade.php');

    expect($result)->toContain('`composer install`')
        ->toContain('`php artisan migrate`');
});

test('php opening tags are preserved through blade rendering for code examples', function (): void {
    $this->mock(GuidelineAssist::class);

    $content = 'Example: <?php echo $greeting; ?>';

    $result = $this->renderer->render($content, '/path/to/guide.blade.php');

    expect($result)->toContain('<?php');
});

test('volt directives are preserved through blade rendering for livewire documentation', function (): void {
    $this->mock(GuidelineAssist::class);

    $content = '@volt("counter") component code @endvolt';

    $result = $this->renderer->render($content, '/path/to/guide.blade.php');

    expect($result)->toContain('@volt')
        ->toContain('@endvolt');
});

test('renderBladeFile returns empty string for non-existent file', function (): void {
    $result = $this->renderer->renderFile('/non/existent/guideline.blade.php');

    expect($result)->toBe('');
});

test('renderBladeFile processes snippets and renders blade in single pipeline', function (): void {
    $this->mock(GuidelineAssist::class);

    $tempFile = sys_get_temp_dir().'/boost_test_'.uniqid().'.blade.php';
    file_put_contents($tempFile, "@boostsnippet('Query', 'php')User::all()@endboostsnippet\n\nVersion: {{ \"1.0\" }}");

    try {
        $result = $this->renderer->renderFile($tempFile);

        expect($result)
            ->toContain('<!-- Query -->')
            ->toContain('```php')
            ->toContain('User::all()')
            ->toContain('```')
            ->toContain('Version: 1.0');
    } finally {
        @unlink($tempFile);
    }
});

test('renderBladeFile clears stored snippets after rendering to prevent leakage between files', function (): void {
    $this->mock(GuidelineAssist::class);

    $tempFile = sys_get_temp_dir().'/boost_test_'.uniqid().'.blade.php';
    file_put_contents($tempFile, "@boostsnippet('Test')content@endboostsnippet");

    try {
        $this->renderer->renderFile($tempFile);

        expect($this->renderer->getStoredSnippets())->toBeEmpty();
    } finally {
        @unlink($tempFile);
    }
});
