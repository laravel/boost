<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Conventions\Concerns\DerivesTitleFromMarkdown;
use Laravel\Boost\Install\Conventions\Contracts\Detector;

/**
 * Half B — inventory Boost's own topic-split guidelines and offer to re-home the directory-specific ones
 * as path-scoped rules (§4). Emitting the scoped candidate is the safe half; removing the chunk from the
 * always-on guideline is left to a reviewed step so we never clobber shipped content (§1 avoid #6).
 */
class GuidelinePartitioner implements Detector
{
    use DerivesTitleFromMarkdown;

    /**
     * Topic (guideline filename, sans .md) → the directory glob it applies to (§3.1). Topics absent from
     * this map are treated as always-on (not directory-specific) and are not offered for scoping.
     *
     * @var array<string, string>
     */
    protected const TOPIC_GLOBS = [
        'eloquent' => 'app/Models/**',
        'advanced-queries' => 'app/Models/**',
        'db-performance' => 'app/Models/**',
        'migrations' => 'database/migrations/**',
        'routing' => 'routes/**',
        'validation' => 'app/Http/Requests/**',
        'blade-views' => 'resources/views/**',
        'testing' => 'tests/**',
        'queue-jobs' => 'app/Jobs/**',
        'config' => 'config/**',
    ];

    public function __construct(protected string $guidelineRulesPath) {}

    public function id(): string
    {
        return 'guideline-partitioner';
    }

    public function detect(): Collection
    {
        if (! is_dir($this->guidelineRulesPath)) {
            return new Collection;
        }

        $detections = new Collection;

        foreach (self::TOPIC_GLOBS as $topic => $glob) {
            $file = $this->guidelineRulesPath.DIRECTORY_SEPARATOR.$topic.'.md';

            if (! is_file($file)) {
                continue;
            }

            $body = trim(File::get($file));

            if ($body === '') {
                continue;
            }

            $detections->push(new Detection(
                id: 'guideline:'.$topic,
                title: $this->title($body, $topic),
                note: $body."\n\n_Boost guideline, scoped to `{$glob}`._",
                glob: $glob,
                confidence: 1.0,
                provenance: Detection::PROVENANCE_BOOST_GUIDELINE,
            ));
        }

        return $detections;
    }
}
