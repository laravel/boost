<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Laravel\Boost\Skills\Remote\InstalledSkill;

class SkillsLock
{
    protected const FILE = 'boost-skills-lock.json';

    protected const VERSION = 1;

    /**
     * @return array<string, InstalledSkill>
     */
    public function getSkills(): array
    {
        $lock = $this->all();

        if (! isset($lock['repositories']) || ! is_array($lock['repositories'])) {
            return [];
        }

        $skills = [];

        foreach ($lock['repositories'] as $source => $repository) {
            if (! is_string($source) || ! is_array($repository)) {
                continue;
            }

            if (! isset($repository['sourceType']) || ! is_string($repository['sourceType'])) {
                continue;
            }

            $sourceType = $repository['sourceType'];

            $repositorySkills = $repository['skills'] ?? [];

            if (! is_array($repositorySkills)) {
                continue;
            }

            foreach ($repositorySkills as $name => $data) {
                if (! is_string($name) || ! is_array($data)) {
                    continue;
                }

                if (! isset($data['computedHash']) || ! is_string($data['computedHash'])) {
                    continue;
                }

                $skills[$name] = InstalledSkill::fromArray($name, [
                    'source' => $source,
                    'sourceType' => $sourceType,
                    'computedHash' => $data['computedHash'],
                ]);
            }
        }

        ksort($skills);

        return $skills;
    }

    public function addSkill(InstalledSkill $skill): void
    {
        $lock = $this->all();

        $lock['version'] = self::VERSION;

        if (! isset($lock['repositories']) || ! is_array($lock['repositories'])) {
            $lock['repositories'] = [];
        }

        if (! isset($lock['repositories'][$skill->source]) || ! is_array($lock['repositories'][$skill->source])) {
            $lock['repositories'][$skill->source] = [
                'sourceType' => $skill->sourceType,
                'skills' => [],
            ];
        }

        $lock['repositories'][$skill->source]['sourceType'] = $skill->sourceType;

        $repositorySkills = $lock['repositories'][$skill->source]['skills'] ?? [];

        if (! is_array($repositorySkills)) {
            $repositorySkills = [];
        }

        $repositorySkills[$skill->name] = ['computedHash' => $skill->hash];
        ksort($repositorySkills);

        $lock['repositories'][$skill->source]['skills'] = $repositorySkills;

        $repositories = $lock['repositories'];
        ksort($repositories);
        $lock['repositories'] = $repositories;

        $this->write($lock);
    }

    public function removeSkill(string $name): void
    {
        $lock = $this->all();

        if (! isset($lock['repositories']) || ! is_array($lock['repositories'])) {
            return;
        }

        $repositories = $lock['repositories'];

        foreach ($repositories as $source => $repository) {
            if (! is_array($repository)) {
                unset($repositories[$source]);

                continue;
            }

            $repositorySkills = $repository['skills'] ?? [];

            if (! is_array($repositorySkills)) {
                $repositorySkills = [];
            }

            unset($repositorySkills[$name]);

            if ($repositorySkills === []) {
                unset($repositories[$source]);

                continue;
            }

            ksort($repositorySkills);
            $repository['skills'] = $repositorySkills;
            $repositories[$source] = $repository;
        }

        $lock['version'] = self::VERSION;
        ksort($repositories);
        $lock['repositories'] = $repositories;

        $this->write($lock);
    }

    public function isValid(): bool
    {
        $path = base_path(self::FILE);

        if (! file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return false;
        }

        $lock = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @return array<string, mixed>
     */
    protected function all(): array
    {
        $path = base_path(self::FILE);

        if (! file_exists($path)) {
            return $this->emptyLock();
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return $this->emptyLock();
        }

        $lock = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($lock)) {
            return $this->emptyLock();
        }

        return $this->normalize($lock);
    }

    /**
     * @param  array<string, mixed>  $lock
     */
    protected function write(array $lock): void
    {
        $path = base_path(self::FILE);

        $json = json_encode($this->normalize($lock), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = '{}';
        }

        file_put_contents(
            $path,
            $json.PHP_EOL
        );
    }

    /**
     * @param  array<string, mixed>  $lock
     * @return array{version: int, repositories: array<string, array{sourceType: string, skills: array<string, array{computedHash: string}>}>}
     */
    protected function normalize(array $lock): array
    {
        $repositories = $lock['repositories'] ?? null;

        if (! is_array($repositories)) {
            return $this->emptyLock();
        }

        return [
            'version' => self::VERSION,
            'repositories' => $this->normalizeRepositories($repositories),
        ];
    }

    /**
     * @param  array<string, mixed>  $repositories
     * @return array<string, array{sourceType: string, skills: array<string, array{computedHash: string}>}>
     */
    protected function normalizeRepositories(array $repositories): array
    {
        $normalized = [];

        foreach ($repositories as $source => $repository) {
            if (! is_array($repository)) {
                continue;
            }

            if (! isset($repository['sourceType']) || ! is_string($repository['sourceType'])) {
                continue;
            }

            $sourceType = $repository['sourceType'];

            $repositorySkills = $repository['skills'] ?? [];

            if (! is_array($repositorySkills)) {
                continue;
            }

            $skills = [];

            foreach ($repositorySkills as $name => $skill) {
                if (! is_string($name)) {
                    continue;
                }

                if (! is_array($skill) || ! isset($skill['computedHash']) || ! is_string($skill['computedHash'])) {
                    continue;
                }

                $skills[$name] = ['computedHash' => $skill['computedHash']];
            }

            if ($skills === []) {
                continue;
            }

            ksort($skills);

            $normalized[$source] = [
                'sourceType' => $sourceType,
                'skills' => $skills,
            ];
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @return array{version: int, repositories: array<string, array{sourceType: string, skills: array<string, array{computedHash: string}>}>}
     */
    protected function emptyLock(): array
    {
        return [
            'version' => self::VERSION,
            'repositories' => [],
        ];
    }
}
