<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Illuminate\Support\Str;

class Config
{
    protected const FILE = 'boost.json';

    public function getGuidelines(): bool
    {
        return (bool) $this->get('guidelines', false);
    }

    public function setGuidelines(bool $enabled): void
    {
        $this->set('guidelines', $enabled);
    }

    /**
     * @return array<int, string>
     */
    public function getSkills(): array
    {
        return $this->get('skills', []);
    }

    /**
     * @param  array<int, string>  $skills
     */
    public function setSkills(array $skills): void
    {
        $this->set('skills', $skills);
    }

    public function hasSkills(): bool
    {
        return $this->getSkills() !== [];
    }

    public function getMcp(): bool
    {
        return $this->get('mcp', false);
    }

    public function setMcp(bool $enabled): void
    {
        $this->set('mcp', $enabled);
    }

    /**
     * @return array<int, string>
     */
    public function getPackages(): array
    {
        return $this->get('packages', []);
    }

    /**
     * @param  array<int, string>  $packages
     */
    public function setPackages(array $packages): void
    {
        $this->set('packages', $packages);
    }

    /**
     * @param  array<int, string>  $agents
     */
    public function setAgents(array $agents): void
    {
        $this->set('agents', $agents);
    }

    /**
     * @return array<int, string>
     */
    public function getAgents(): array
    {
        return $this->get('agents', []);
    }

    public function setHerdMcp(bool $installed): void
    {
        $this->set('herd_mcp', $installed);
    }

    public function getHerdMcp(): bool
    {
        return $this->get('herd_mcp', false);
    }

    public function setNightwatchMcp(bool $installed): void
    {
        $this->set('nightwatch_mcp', $installed);
    }

    public function getNightwatchMcp(): bool
    {
        return $this->get('nightwatch_mcp', false);
    }

    public function setSail(bool $useSail): void
    {
        $this->set('sail', $useSail);
    }

    public function getSail(): bool
    {
        return $this->get('sail', false);
    }

    public function isValid(): bool
    {
        $path = base_path(self::FILE);

        if (! file_exists($path)) {
            return false;
        }

        json_decode(file_get_contents($path), true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function flush(): void
    {
        $path = base_path(self::FILE);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        $config = $this->all();

        return data_get($config, $key, $default);
    }

    protected function set(string $key, mixed $value): void
    {
        $config = array_filter($this->all(), fn ($value): bool => $value !== null && $value !== []);

        data_set($config, $key, $value);

        ksort($config);

        $path = base_path(self::FILE);

        file_put_contents($path, Str::of(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->append(PHP_EOL));
    }

    protected function all(): array
    {
        $path = base_path(self::FILE);

        if (! file_exists($path)) {
            return [];
        }

        $config = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $config ?? [];
    }
}
