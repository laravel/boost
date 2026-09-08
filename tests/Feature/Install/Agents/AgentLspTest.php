<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\ClaudeCodeLspWriter;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

beforeEach(function (): void {
    $this->agent = new ClaudeCode(Mockery::mock(DetectionStrategyFactory::class));
});

afterEach(function (): void {
    if (is_dir(base_path('.boost-test-lsp/readonly'))) {
        chmod(base_path('.boost-test-lsp/readonly'), 0755);
    }

    File::deleteDirectory(base_path('.boost-test-lsp'));
});

test('ClaudeCode installs the LSP registration as a project plugin', function (): void {
    config(['boost.agents.claude_code.skills_path' => '.boost-test-lsp/skills']);

    $result = (new ClaudeCodeLspWriter($this->agent))->write();

    $pluginDir = base_path('.boost-test-lsp/skills/laravel-lsp');
    $manifest = json_decode(file_get_contents($pluginDir.'/.claude-plugin/plugin.json'), true);
    $servers = json_decode(file_get_contents($pluginDir.'/.lsp.json'), true);

    expect($result)->toBe(ClaudeCodeLspWriter::SUCCESS)
        ->and($manifest['name'])->toBe('laravel-lsp')
        ->and($servers['laravel']['command'])->toBe('laravel-lsp')
        ->and($servers['laravel']['extensionToLanguage'])->toBe(['.php' => 'php', '.blade.php' => 'blade']);
});

test('ClaudeCode derives the plugin path from the skills path', function (): void {
    config(['boost.agents.claude_code.skills_path' => '.boost-test-lsp/custom-skills']);

    expect($this->agent->lspConfigPath())->toBe('.boost-test-lsp/custom-skills'.DIRECTORY_SEPARATOR.'laravel-lsp');
});

test('installing twice keeps a single valid registration', function (): void {
    config(['boost.agents.claude_code.skills_path' => '.boost-test-lsp/skills']);

    $writer = new ClaudeCodeLspWriter($this->agent);

    expect($writer->write())->toBe(ClaudeCodeLspWriter::SUCCESS)
        ->and($writer->write())->toBe(ClaudeCodeLspWriter::SUCCESS);

    $servers = json_decode(file_get_contents(base_path('.boost-test-lsp/skills/laravel-lsp/.lsp.json')), true);

    expect($servers)->toHaveCount(1)
        ->and($servers['laravel']['command'])->toBe('laravel-lsp');
});

test('the writer throws when the plugin directory is not writable', function (): void {
    config(['boost.agents.claude_code.skills_path' => '.boost-test-lsp/readonly/skills']);

    File::makeDirectory(base_path('.boost-test-lsp'), 0755, true);
    File::makeDirectory(base_path('.boost-test-lsp/readonly'), 0555);

    expect(fn () => (new ClaudeCodeLspWriter($this->agent))->write())->toThrow(RuntimeException::class);
})->skipOnWindows()->skip(
    fn (): bool => function_exists('posix_getuid') && posix_getuid() === 0,
    'Directory permissions do not apply to root.',
);
