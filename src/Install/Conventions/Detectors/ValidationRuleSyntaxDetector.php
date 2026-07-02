<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\AbstractDetector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;

/**
 * Resolve whether Form Requests write validation rules as pipe-delimited strings ('required|max:255') or arrays (['required', 'max:255']); validation.md prefers arrays but explicitly punts to "match whatever notation the project already uses", so recording the team's actual notation helps a small model match it.
 */
class ValidationRuleSyntaxDetector extends AbstractDetector
{
    public function id(): string
    {
        return 'validation-rule-syntax';
    }

    public function detect(DetectionContext $context): Collection
    {
        $tally = ['pipe' => 0, 'array' => 0];
        $votedFiles = [];

        foreach ($context->sampler->phpFiles($context->roots, 'Http/Requests') as $file) {
            $contents = $context->sampler->contents($file->getPathname());

            if (! str_contains($contents, 'function rules')) {
                continue;
            }

            $pipeDelimitedRules = preg_match_all("/=>\s*'[^']*\|[^']*'/", $contents);
            $arrayNotationRules = preg_match_all("/=>\s*\[\s*'/", $contents);

            if ($pipeDelimitedRules > $arrayNotationRules) {
                $tally['pipe']++;
                $votedFiles[] = $file->getPathname();
            } elseif ($arrayNotationRules > $pipeDelimitedRules) {
                $tally['array']++;
                $votedFiles[] = $file->getPathname();
            }
        }

        $dominant = $this->dominant($tally);

        if ($dominant === null) {
            return new Collection;
        }

        $isPipe = $dominant['winner'] === 'pipe';
        $syntax = $isPipe ? 'pipe-delimited string' : 'array';
        $example = $isPipe ? "'required|max:255'" : "['required', 'max:255']";

        return new Collection([
            new Detection(
                id: $this->id(),
                title: 'Write validation rules with '.($isPipe ? 'pipe syntax' : 'array syntax'),
                note: "This project's Form Requests write validation rules as {$syntax}s, e.g. `{$example}` (detected in {$dominant['votes']}/{$dominant['total']} form requests). Follow the same notation for new rules.",
                glob: $this->globForFiles($votedFiles, $context->basePath),
                confidence: $dominant['confidence'],
            ),
        ]);
    }
}
