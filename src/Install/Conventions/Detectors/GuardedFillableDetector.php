<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\AbstractDetector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;

/**
 * Resolve which mass-assignment guard the project actually uses ($fillable vs $guarded); security.md punts this to "define one or the other", so recording the team's choice adds signal a small model won't infer reliably.
 */
class GuardedFillableDetector extends AbstractDetector
{
    public function id(): string
    {
        return 'model-mass-assignment';
    }

    public function detect(DetectionContext $context): Collection
    {
        $tally = ['$fillable' => 0, '$guarded' => 0];
        $votedFiles = [];

        foreach ($context->sampler->phpFiles($context->roots, 'Models') as $file) {
            $contents = $context->sampler->contents($file->getPathname());

            $hasFillable = preg_match('/protected\s+\$fillable\b/', $contents) === 1;
            $hasGuarded = preg_match('/protected\s+\$guarded\b/', $contents) === 1;

            if ($hasFillable && ! $hasGuarded) {
                $tally['$fillable']++;
                $votedFiles[] = $file->getPathname();
            } elseif ($hasGuarded && ! $hasFillable) {
                $tally['$guarded']++;
                $votedFiles[] = $file->getPathname();
            }
        }

        $dominant = $this->dominant($tally);

        if ($dominant === null) {
            return new Collection;
        }

        $property = $dominant['winner'];
        $glob = $this->globForFiles($votedFiles, $context->basePath);

        $note = $property === '$fillable'
            ? "This project's Eloquent models declare mass-assignable attributes with a `\$fillable` allowlist"
            : "This project's Eloquent models block mass assignment with a `\$guarded` denylist";

        $note .= " (detected in {$dominant['votes']}/{$dominant['total']} models). Follow the same approach for new models.";

        return new Collection([
            new Detection(
                id: $this->id(),
                title: "Guard model mass assignment with {$property}",
                note: $note,
                glob: $glob,
                confidence: $dominant['confidence'],
            ),
        ]);
    }
}
