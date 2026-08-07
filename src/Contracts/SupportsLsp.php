<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for agents that support Laravel LSP registration.
 */
interface SupportsLsp
{
    /**
     * Get the directory path where the Laravel LSP registration should be written.
     */
    public function lspConfigPath(): string;
}
