<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RuleComposer
{
    /** @var Collection<string, array{paths: array<int, string>, content: string}>|null */
    protected ?Collection $rules = null;

    public function __construct(protected GuidelineComposer $guidelines) {}

    /**
     * One entry per rendered `@scoped` block, keyed by guideline key and block index.
     *
     * @return Collection<string, array{paths: array<int, string>, content: string}>
     */
    public function rules(): Collection
    {
        if ($this->rules instanceof Collection) {
            return $this->rules;
        }

        $rules = collect();

        $this->guidelines->resolvedGuidelines()->each(function (array $guideline, string $key) use ($rules): void {
            foreach ($guideline['scoped'] ?? [] as $index => $block) {
                if ($block['paths'] === []) {
                    continue;
                }

                if ($block['body'] === '') {
                    continue;
                }

                $rules->put($key.'#'.$index, [
                    'paths' => $block['paths'],
                    'content' => $block['body'],
                ]);
            }
        });

        return $this->rules = $rules;
    }

    /**
     * Merge rules that apply to the exact same globs into a single managed rule file.
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
        $firstLine = Str::of($content)->trim()->before("\n")->trim()->value();

        if (preg_match('/^#+\s+(?<heading>.+)$/', $firstLine, $matches) === 1) {
            return trim($matches['heading']);
        }

        return Str::headline($fallbackSlug);
    }

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $taken
     */
    protected function uniqueSlug(array $paths, array $taken): string
    {
        $lastSegments = collect($paths)
            ->map(fn (string $glob): string => (string) Str::of($glob)
                ->explode('/')
                ->filter(fn (string $segment): bool => filled($segment) && ! Str::contains($segment, ['*', '.']))
                ->last())
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
