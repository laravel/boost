<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Illuminate\Support\Str;

class Config
{
    protected const FILE = 'boost.json';

    public function exists(): bool
    {
        return file_exists(base_path(self::FILE));
    }

    /**
     * @return array<int, string>
     */
    public function getAiGuidelines(): array
    {
        return $this->get('guidelines', []);
    }

    /**
     * @param  array<int, string>  $guidelines
     */
    public function setAiGuidelines(array $guidelines): void
    {
        $this->set('guidelines', $guidelines);
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        $config = $this->all();

        return data_get($config, $key, $default);
    }

    protected function set(string $key, mixed $value): void
    {
        $config = array_filter($this->all());

        data_set($config, $key, $value);

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
