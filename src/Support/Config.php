<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Illuminate\Support\Str;

class Config
{
    protected const FILE = 'boost.json';

    public const SKILL_SOURCE_CUSTOM = 'custom';

    public const SKILL_SOURCE_GITHUB = 'github';

    public const SKILL_SOURCE_OFFICIAL = 'official';

    protected const LEGACY_OFFICIAL_SKILLS_SOURCE = 'laravel/boost';

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
        return array_keys($this->getSkillMetadata());
    }

    /**
     * @param  array<int, string>  $skills
     * @param  array<string, array<string, string>>  $metadata
     */
    public function setSkills(array $skills, array $metadata = []): void
    {
        $currentMetadata = $this->getSkillMetadata();
        $selectedSkills = [];

        foreach ($skills as $skillName) {
            if ($skillName === '') {
                continue;
            }

            $selectedSkills[$skillName] = $this->resolveSkillMetadata(
                skillName: $skillName,
                currentMetadata: $currentMetadata[$skillName] ?? [],
                incomingMetadata: $metadata[$skillName] ?? $currentMetadata[$skillName] ?? ['source' => self::SKILL_SOURCE_OFFICIAL],
            );
        }

        $this->set('skills', $this->sortSkillsByName($selectedSkills));
    }

    public function hasSkills(): bool
    {
        return $this->getSkills() !== [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getTrackedSkills(): array
    {
        return $this->getSkillMetadata();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getSkillMetadata(): array
    {
        return $this->extractSkillMetadata($this->getRawSkills());
    }

    /**
     * @param  array<string, array<string, string>|string>  $skills
     */
    public function trackSkills(array $skills): void
    {
        $metadata = $this->getSkillMetadata();

        foreach ($skills as $skillName => $skillMetadata) {
            if ($skillName === '') {
                continue;
            }

            $metadata[$skillName] = $this->normalizeSkillMetadata($skillName, $skillMetadata);
        }

        $this->set('skills', $this->sortSkillsByName($metadata));
    }

    public function trackSkill(string $skillName, string $source): void
    {
        $this->trackSkills([$skillName => $source]);
    }

    protected function extractSkillMetadata(array $currentConfig): array
    {
        $metadata = [];
        $isList = array_is_list($currentConfig);

        foreach ($currentConfig as $key => $value) {
            if ($isList) {
                if (is_string($value) && $value !== '') {
                    $metadata[$value] = ['source' => self::SKILL_SOURCE_CUSTOM];
                }

                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                $source = is_string($key) && $key !== '' ? $key : self::SKILL_SOURCE_CUSTOM;

                foreach ($value as $skillName) {
                    if (is_string($skillName) && $skillName !== '') {
                        $metadata[$skillName] = $this->metadataFromLegacySource($skillName, $source);
                    }
                }

                continue;
            }

            if (! is_string($key) || $key === '') {
                continue;
            }

            if (is_bool($value)) {
                $metadata[$key] = ['source' => $value ? self::SKILL_SOURCE_OFFICIAL : self::SKILL_SOURCE_CUSTOM];

                continue;
            }

            if (is_string($value)) {
                $metadata[$key] = $this->metadataFromLegacySource($key, $value);

                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->normalizeSkillMetadata($key, $value);
            }
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>|string  $metadata
     * @return array<string, string>
     */
    protected function normalizeSkillMetadata(string $skillName, array|string $metadata): array
    {
        if (is_string($metadata)) {
            return $this->metadataFromLegacySource($skillName, $metadata);
        }

        $source = $metadata['source'] ?? self::SKILL_SOURCE_CUSTOM;

        if (! is_string($source) || $source === '') {
            return ['source' => self::SKILL_SOURCE_CUSTOM];
        }

        if ($source === self::SKILL_SOURCE_GITHUB) {
            return $this->githubMetadata(
                skillName: $skillName,
                repo: $metadata['repo'] ?? null,
                path: $metadata['path'] ?? null,
            );
        }

        if ($source === self::SKILL_SOURCE_OFFICIAL || $source === self::SKILL_SOURCE_CUSTOM) {
            return ['source' => $source];
        }

        return $this->metadataFromLegacySource($skillName, $source);
    }

    /**
     * @return array<string, string>
     */
    protected function metadataFromLegacySource(string $skillName, string $source): array
    {
        $source = trim($source, '/');

        if ($source === '' || $source === self::SKILL_SOURCE_CUSTOM) {
            return ['source' => self::SKILL_SOURCE_CUSTOM];
        }

        if ($source === self::SKILL_SOURCE_OFFICIAL || $source === self::LEGACY_OFFICIAL_SKILLS_SOURCE) {
            return ['source' => self::SKILL_SOURCE_OFFICIAL];
        }

        $parts = explode('/', $source);

        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            return ['source' => self::SKILL_SOURCE_CUSTOM];
        }

        $repo = $parts[0].'/'.$parts[1];
        $basePath = implode('/', array_slice($parts, 2));
        $path = $basePath === '' ? $skillName : $basePath.'/'.$skillName;

        return $this->githubMetadata($skillName, $repo, $path);
    }

    /**
     * @return array<string, string>
     */
    protected function githubMetadata(string $skillName, mixed $repo, mixed $path = null): array
    {
        if (! is_string($repo) || $repo === '') {
            return ['source' => self::SKILL_SOURCE_CUSTOM];
        }

        $metadata = [
            'source' => self::SKILL_SOURCE_GITHUB,
            'repo' => $repo,
        ];

        if (is_string($path) && $path !== '' && $path !== $skillName) {
            $metadata['path'] = $path;
        }

        return $metadata;
    }

    /**
     * @param  array<string, string>  $currentMetadata
     * @param  array<string, string>  $incomingMetadata
     * @return array<string, string>
     */
    protected function resolveSkillMetadata(string $skillName, array $currentMetadata, array $incomingMetadata): array
    {
        $currentMetadata = $this->normalizeSkillMetadata($skillName, $currentMetadata);
        $incomingMetadata = $this->normalizeSkillMetadata($skillName, $incomingMetadata);

        if (($currentMetadata['source'] ?? null) === self::SKILL_SOURCE_GITHUB && ($incomingMetadata['source'] ?? null) === self::SKILL_SOURCE_CUSTOM) {
            return $currentMetadata;
        }

        return $incomingMetadata;
    }

    /**
     * @param  array<string, array<string, string>>  $metadata
     * @return array<string, array<string, string>>
     */
    protected function sortSkillsByName(array $metadata): array
    {
        ksort($metadata);

        return $metadata;
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

    public function setNightwatch(bool $installed): void
    {
        $this->set('nightwatch', $installed);
    }

    public function getNightwatch(): bool
    {
        return (bool) $this->get('nightwatch', $this->get('nightwatch_mcp', false));
    }

    public function setCloud(bool $installed): void
    {
        $this->set('cloud', $installed);
    }

    public function getCloud(): bool
    {
        return $this->get('cloud', false);
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
