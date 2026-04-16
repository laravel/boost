<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Illuminate\Support\Str;

class Config
{
    protected const FILE = 'boost.json';

    protected const DEFAULT_SKILLS_SOURCE = 'laravel/boost';

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
        return array_keys($this->extractSourceMap($this->getRawSkills()));
    }

    /**
     * @param  array<int, string>  $skills
     */
    public function setSkills(array $skills): void
    {
        $sourceMap = $this->extractSourceMap($this->getRawSkills());
        $selectedSkills = [];

        foreach ($skills as $skillName) {
            if ($skillName === '') {
                continue;
            }

            $selectedSkills[$skillName] = $sourceMap[$skillName] ?? self::DEFAULT_SKILLS_SOURCE;
        }

        $this->set('skills', $this->groupSkillsBySource($selectedSkills));
    }

    public function hasSkills(): bool
    {
        return $this->getSkills() !== [];
    }

    public function getTrackedSkills(): array
    {
        $tracked = [];

        foreach ($this->extractSourceMap($this->getRawSkills()) as $skillName => $source) {
            if ($source !== self::DEFAULT_SKILLS_SOURCE) {
                $tracked[$skillName] = ['source' => $source];
            }
        }

        return $tracked;
    }

    public function trackSkills(array $skillsWithSource): void
    {
        $sourceMap = $this->extractSourceMap($this->getRawSkills());

        foreach ($skillsWithSource as $skillName => $source) {
            if (is_string($skillName) && $skillName !== '' && is_string($source) && $source !== '') {
                $sourceMap[$skillName] = $source;
            }
        }

        $this->set('skills', $this->groupSkillsBySource($sourceMap));
    }

    public function trackSkill(string $skillName, string $source): void
    {
        $this->trackSkills([$skillName => $source]);
    }

    protected function extractSourceMap(array $currentConfig): array
    {
        $sourceMap = [];
        $isList = array_is_list($currentConfig);

        foreach ($currentConfig as $key => $value) {
            if ($isList) {
                if (is_string($value) && $value !== '') {
                    $sourceMap[$value] = self::DEFAULT_SKILLS_SOURCE;
                }

                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                $source = is_string($key) && $key !== '' ? $key : self::DEFAULT_SKILLS_SOURCE;

                foreach ($value as $skillName) {
                    if (is_string($skillName) && $skillName !== '') {
                        $sourceMap[$skillName] = $source;
                    }
                }

                continue;
            }

            if (! is_string($key) || ! is_array($value)) {
                continue;
            }

            $source = $value['source'] ?? self::DEFAULT_SKILLS_SOURCE;

            if (is_string($source)) {
                $sourceMap[$key] = $source !== '' ? $source : self::DEFAULT_SKILLS_SOURCE;
            }
        }

        return $sourceMap;
    }

    protected function groupSkillsBySource(array $sourceMap): array
    {
        $grouped = [];

        foreach ($sourceMap as $skillName => $source) {
            if (! is_string($skillName) || $skillName === '' || ! is_string($source) || $source === '') {
                continue;
            }

            if (! isset($grouped[$source])) {
                $grouped[$source] = [];
            }

            $grouped[$source][$skillName] = $skillName;
        }

        foreach ($grouped as &$skillList) {
            $skillList = array_values($skillList);
            sort($skillList);
        }

        ksort($grouped);

        return $grouped;
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

    protected function getRawSkills(): array
    {
        $skills = $this->get('skills', []);

        return is_array($skills) ? $skills : [];
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
