<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\RendersBladeGuidelines;

class RuleComposer
{
    use RendersBladeGuidelines;

    /** @var Collection<string, array{paths: array<int, string>, content: string, third_party: bool}>|null */
    protected ?Collection $rules = null;

    public function __construct(protected GuidelineComposer $guidelines) {}

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return $this->guidelines->guidelineAssist();
    }

    /**
     * All `@scoped` blocks found inside GuidelineComposer's already-resolved guideline files,
     * one entry per block.
     *
     * A scoped block lives in the same file as its guideline's always-inline content, so
     * whatever already decided that file's fate — package priority/exclusion, a user override
     * in `.ai/guidelines/`, `boost.guidelines.exclude` — decides the block's fate too. There is
     * no separate resolution path to keep in sync.
     *
     * @return Collection<string, array{paths: array<int, string>, content: string, third_party: bool}>
     */
    public function rules(): Collection
    {
        if ($this->rules instanceof Collection) {
            return $this->rules;
        }

        $rules = collect();

        $this->guidelines->resolvedGuidelines()->each(function (array $guideline, string $key) use ($rules): void {
            if ($guideline['path'] === null) {
                return;
            }

            foreach ($this->scopedBlocksIn($guideline['path']) as $index => $block) {
                if ($block['paths'] === []) {
                    continue;
                }

                if ($block['body'] === '') {
                    continue;
                }

                $rules->put($key.'#'.$index, [
                    'paths' => $block['paths'],
                    'content' => trim($this->renderBladeString($block['body'], $guideline['path'])),
                    'third_party' => $guideline['third_party'],
                ]);
            }
        });

        return $this->rules = $rules;
    }

    /**
     * Group scoped rules by their normalized paths set, merging rules that apply to the
     * exact same globs into a single managed rule file payload.
     *
     * @return Collection<string, array{paths: array<int, string>, title: string, content: string}>
     */
    public function composeManaged(): Collection
    {
        $slugs = [];

        return $this->rules()
            ->groupBy(fn (array $rule): string => $this->pathsKey($rule['paths']))
            ->mapWithKeys(function (Collection $group) use (&$slugs): array {
                $paths = collect($group->first()['paths'])
                    ->map(fn (string $path): string => trim($path))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $content = MarkdownFormatter::format(
                    $group->map(fn (array $rule): string => trim($rule['content']))->filter()->join("\n\n")
                );

                $slug = $this->uniqueSlug($paths, $slugs);
                $slugs[] = $slug;

                return [$slug => [
                    'paths' => $paths,
                    'title' => $this->titleFor($content, $slug),
                    'content' => $content,
                ]];
            });
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function pathsKey(array $paths): string
    {
        return collect($paths)->map(fn (string $path): string => trim($path))->unique()->sort()->values()->join('|');
    }

    protected function titleFor(string $content, string $fallbackSlug): string
    {
        $heading = Str::of($content)->after('# ')->before("\n")->trim()->value();

        return filled($heading) ? $heading : Str::headline($fallbackSlug);
    }

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $taken
     */
    protected function uniqueSlug(array $paths, array $taken): string
    {
        $lastSegments = collect($paths)
            ->map(fn (string $glob): array => Str::of($glob)
                ->explode('/')
                ->filter(fn (string $segment): bool => filled($segment) && ! str_contains($segment, '*') && ! str_contains($segment, '.'))
                ->values()
                ->all())
            ->map(fn (array $segments): string => (string) end($segments))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $base = $lastSegments->isEmpty() ? 'rules' : Str::slug(Str::snake($lastSegments->join(' ')));

        if ($base === '') {
            $base = 'rules';
        }

        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $suffix = 2;

        while (in_array($base.'-'.$suffix, $taken, true)) {
            $suffix++;
        }

        return $base.'-'.$suffix;
    }
}
