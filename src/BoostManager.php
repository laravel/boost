<?php

declare(strict_types=1);

namespace Laravel\Boost;

use InvalidArgumentException;
use Laravel\Boost\Install\Agent\Agent;
use Laravel\Boost\Install\Agent\ClaudeCode;
use Laravel\Boost\Install\Agent\Codex;
use Laravel\Boost\Install\Agent\Copilot;
use Laravel\Boost\Install\Agent\Cursor;
use Laravel\Boost\Install\Agent\Gemini;
use Laravel\Boost\Install\Agent\Junie;
use Laravel\Boost\Install\Agent\OpenCode;

class BoostManager
{
    /** @var array<string, class-string<Agent>> */
    private array $agents = [
        'phpstorm' => Junie::class,
        'cursor' => Cursor::class,
        'claudecode' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
        'opencode' => OpenCode::class,
        'gemini' => Gemini::class,
    ];

    /**
     * @param  class-string<Agent>  $className
     */
    public function registerAgent(string $key, string $className): void
    {
        if (array_key_exists($key, $this->agents)) {
            throw new InvalidArgumentException("Agent '{$key}' is already registered");
        }

        $this->agents[$key] = $className;
    }

    /**
     * @return array<string, class-string<Agent>>
     */
    public function getAgents(): array
    {
        return $this->agents;
    }
}
