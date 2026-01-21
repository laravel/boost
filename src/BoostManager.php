<?php

declare(strict_types=1);

namespace Laravel\Boost;

use InvalidArgumentException;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\Gemini;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Agents\OpenCode;

class BoostManager
{
    /** @var array<string, class-string<Agent>> */
    private array $agents = [
        'junie' => Junie::class,
        'cursor' => Cursor::class,
        'claude_code' => ClaudeCode::class,
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
