<?php

declare(strict_types=1);

namespace Laravel\Boost;

use InvalidArgumentException;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\Gemini;
use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\CodeEnvironment\VSCode;

class BoostManager
{
    /** @var array<string, class-string<CodeEnvironment>> */
    private array $codeEnvironments = [
        'phpstorm' => PhpStorm::class,
        'vscode' => VSCode::class,
        'cursor' => Cursor::class,
        'claudecode' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
        'opencode' => OpenCode::class,
        'gemini' => Gemini::class,
    ];

    /**
     * @param  class-string<CodeEnvironment>  $className
     */
    public function registerCodeEnvironment(string $key, string $className): void
    {
        if (array_key_exists($key, $this->codeEnvironments)) {
            throw new InvalidArgumentException("Code environment '{$key}' is already registered");
        }

        $this->codeEnvironments[$key] = $className;
    }

    /**
     * @return array<string, class-string<CodeEnvironment>>
     */
    public function getCodeEnvironments(): array
    {
        return $this->codeEnvironments;
    }
}
