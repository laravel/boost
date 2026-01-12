<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class ExecutableConfig
{
    public function __construct(
        public string $php = 'php',
        public string $artisan = 'artisan',
        public string $composer = 'composer',
        public string $sail = 'vendor/bin/sail',
        public string $vendorBin = 'vendor/bin',
        public ?string $nodeManager = null,
        public ?string $nodePath = null,
    ) {}

    public static function fromConfig(array $config): self
    {
        return new self(
            php: $config['php'] ?? 'php',
            artisan: $config['artisan'] ?? 'artisan',
            composer: $config['composer'] ?? 'composer',
            sail: $config['sail'] ?? 'vendor/bin/sail',
            vendorBin: $config['vendor_bin'] ?? 'vendor/bin',
            nodeManager: $config['node']['manager'] ?? null,
            nodePath: $config['node']['path'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'php' => $this->php,
            'artisan' => $this->artisan,
            'composer' => $this->composer,
            'sail' => $this->sail,
            'vendor_bin' => $this->vendorBin,
            'node' => [
                'manager' => $this->nodeManager,
                'path' => $this->nodePath,
            ],
        ];
    }

    public function hasCustomPaths(): bool
    {
        return $this->php !== 'php'
            || $this->artisan !== 'artisan'
            || $this->composer !== 'composer'
            || $this->vendorBin !== 'vendor/bin'
            || $this->nodePath !== null;
    }
}
