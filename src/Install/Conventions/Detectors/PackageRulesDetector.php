<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Conventions\Concerns\DerivesTitleFromMarkdown;
use Laravel\Boost\Install\Conventions\Contracts\Detector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Support\Composer;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * Source C — surface path-scoped rules a package author ships in resources/boost/rules/*.md as opt-in candidates in the same multiselect (§3.2).
 */
class PackageRulesDetector implements Detector
{
    use DerivesTitleFromMarkdown;

    public function id(): string
    {
        return 'package-rules';
    }

    public function detect(): Collection
    {
        $detections = new Collection;

        foreach (Composer::packagesDirectoriesWithBoostRules() as $package => $directory) {
            foreach ($this->ruleFiles($directory) as $file) {
                $parsed = $this->parse(File::get($file));

                if ($parsed['paths'] === []) {
                    continue;
                }

                $slug = basename($file, '.md');
                $title = $this->title($parsed['body'], $slug);
                $note = trim($parsed['body'])."\n\n_Provided by {$package}._";

                foreach ($parsed['paths'] as $index => $glob) {
                    $detections->push(new Detection(
                        id: 'package:'.$package.':'.$slug.':'.$index,
                        title: $title,
                        note: $note,
                        glob: $glob,
                        confidence: 1.0,
                        provenance: Detection::packageProvenance($package),
                    ));
                }
            }
        }

        return $detections;
    }

    /**
     * @return array<int, string>
     */
    protected function ruleFiles(string $directory): array
    {
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.md');

        return $files === false ? [] : $files;
    }

    /**
     * @return array{paths: array<int, string>, body: string}
     */
    protected function parse(string $raw): array
    {
        $raw = (string) preg_replace('/\R/', "\n", $raw);

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $matches) !== 1) {
            return ['paths' => [], 'body' => $raw];
        }

        try {
            $front = Yaml::parse($matches[1]) ?: [];
        } catch (Throwable) {
            $front = [];
        }

        return [
            'paths' => array_values(array_filter((array) ($front['paths'] ?? []), is_string(...))),
            'body' => $matches[2],
        ];
    }
}
