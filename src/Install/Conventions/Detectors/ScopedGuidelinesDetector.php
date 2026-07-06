<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Detectors;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Install\Conventions\Concerns\DerivesTitleFromMarkdown;
use Laravel\Boost\Install\Conventions\Contracts\Detector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\GuidelineComposer;

/**
 * Offer directory-specific composed guidelines as opt-in path-scoped rules. Recording one stores the
 * guideline key in the rule file's frontmatter, which the composer uses to stop inlining that guideline —
 * shrinking the generated agent file. Deleting the rule file restores the inline guideline.
 */
class ScopedGuidelinesDetector implements Detector
{
    use DerivesTitleFromMarkdown;

    /**
     * Guideline group (first segment of the composed guideline key) → the directory glob it applies to.
     * Groups absent from this map are treated as always-on and are never offered for scoping.
     *
     * @var array<string, string>
     */
    protected const GUIDELINE_GLOBS = [
        'pest' => 'tests/**',
        'phpunit' => 'tests/**',
        'livewire' => 'app/Livewire/**',
        'volt' => 'resources/views/**',
        'folio' => 'resources/views/pages/**',
        'inertia-react' => 'resources/js/**',
        'inertia-vue' => 'resources/js/**',
        'inertia-svelte' => 'resources/js/**',
        'tailwindcss' => 'resources/**',
        'fluxui-free' => 'resources/views/**',
        'fluxui-pro' => 'resources/views/**',
    ];

    public function __construct(protected GuidelineComposer $composer) {}

    public function id(): string
    {
        return 'scoped-guidelines';
    }

    public function detect(): Collection
    {
        return $this->composer->guidelines()
            ->filter(fn (array $guideline, string $key): bool => isset(self::GUIDELINE_GLOBS[Str::before($key, '/')]))
            ->map(function (array $guideline, string $key): Detection {
                $glob = self::GUIDELINE_GLOBS[Str::before($key, '/')];

                return new Detection(
                    id: 'scoped-guideline:'.$key,
                    title: $this->title($guideline['content'], $key),
                    note: trim((string) $guideline['content'])."\n\n_Boost `{$key}` guideline, scoped to `{$glob}`._",
                    glob: $glob,
                    confidence: 1.0,
                    provenance: Detection::PROVENANCE_BOOST_GUIDELINE,
                    guidelineKey: $key,
                );
            })
            ->values();
    }
}
