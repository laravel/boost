<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\AbstractDetector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;

/**
 * Resolve whether models declare query scopes with the PHP-8 `#[Scope]` attribute or the `scopeXxx()` naming convention; eloquent.md shows the naming style but does not forbid the attribute, so recording the team's actual choice is a punt a small model won't resolve reliably. Scopes are the one model "attribute vs non-attribute" decision where both camps emit detectable code (observers/policies/factories have no detectable non-attribute side).
 */
class QueryScopeStyleDetector extends AbstractDetector
{
    public function id(): string
    {
        return 'query-scope-style';
    }

    public function detect(DetectionContext $context): Collection
    {
        $tally = ['attribute' => 0, 'naming' => 0];
        $votedFiles = [];

        foreach ($context->sampler->phpFiles($context->roots, 'Models') as $file) {
            $contents = $context->sampler->contents($file->getPathname());

            $attribute = preg_match_all('/#\[\s*Scope\s*\]/', $contents);
            $naming = preg_match_all('/function\s+scope[A-Z]\w*\s*\(/', $contents);

            if ($attribute === 0 && $naming === 0) {
                continue;
            }

            $tally['attribute'] += $attribute;
            $tally['naming'] += $naming;
            $votedFiles[] = $file->getPathname();
        }

        $dominant = $this->dominant($tally);

        if ($dominant === null) {
            return new Collection;
        }

        $isAttribute = $dominant['winner'] === 'attribute';
        $style = $isAttribute ? 'the `#[Scope]` attribute' : 'the `scopeXxx()` naming convention';
        $example = $isAttribute
            ? '#[Scope] protected function active(Builder $query): Builder'
            : 'public function scopeActive(Builder $query): Builder';

        return new Collection([
            new Detection(
                id: $this->id(),
                title: 'Declare query scopes with '.($isAttribute ? 'the #[Scope] attribute' : 'scopeXxx() naming'),
                note: "This project's models define query scopes using {$style}, e.g. `{$example}` (detected in {$dominant['votes']}/{$dominant['total']} scopes). Follow the same style for new scopes.",
                glob: $this->globForFiles($votedFiles, $context->basePath),
                confidence: $dominant['confidence'],
            ),
        ]);
    }
}
