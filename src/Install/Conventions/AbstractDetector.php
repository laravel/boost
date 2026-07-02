<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

use Laravel\Boost\Install\Conventions\Contracts\Detector;

abstract class AbstractDetector implements Detector
{
    /** Minimum number of files exhibiting the decision before we record anything (§5.1: n<5 is insufficient evidence). */
    protected const MIN_SAMPLE = 5;

    /** Gate on the Wilson score lower bound rather than the raw ratio, so a small sample cannot over-claim (9/10 ≠ 90/100). */
    protected const WILSON_FLOOR = 0.5;

    /** z for a 95% confidence interval. */
    protected const Z = 1.96;

    /**
     * Reduce a style tally to its dominant winner, or null when there is too little evidence or the styles are mixed.
     *
     * Gating uses the Wilson score lower bound (§5.1); the returned confidence is the raw ratio, which is
     * friendlier to display and drives the pre-select threshold. A "vote" is one observation of the
     * decision — usually one per file, but a detector may vote at a finer grain (e.g. per enum case) when
     * that is the natural unit for the signal; it must document doing so.
     *
     * @param  array<string, int>  $tally  style => number of votes for it
     * @return array{winner: string, votes: int, total: int, confidence: float}|null
     */
    protected function dominant(array $tally): ?array
    {
        $tally = array_filter($tally, fn (int $votes): bool => $votes > 0);
        $total = array_sum($tally);

        if ($total < static::MIN_SAMPLE) {
            return null;
        }

        arsort($tally);
        $winner = (string) array_key_first($tally);
        $votes = $tally[$winner];

        if ($this->wilsonLower($votes, $total) < static::WILSON_FLOOR) {
            return null;
        }

        return ['winner' => $winner, 'votes' => $votes, 'total' => $total, 'confidence' => $votes / $total];
    }

    /**
     * Lower bound of the Wilson score interval for a binomial proportion.
     */
    protected function wilsonLower(int $successes, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $z = static::Z;
        $z2 = $z * $z;
        $phat = $successes / $total;

        $center = ($phat + $z2 / (2 * $total)) / (1 + $z2 / $total);
        $margin = ($z / (1 + $z2 / $total)) * sqrt($phat * (1 - $phat) / $total + $z2 / (4 * $total * $total));

        return $center - $margin;
    }

    /**
     * Derive a base-relative glob covering the directory the given files share.
     *
     * When the files live under one directory it returns `<relative-dir>/**`. When they span unrelated
     * source roots (so the shared prefix collapses to the base path) it falls back to `**\/<dir>/**` when
     * they at least share a trailing directory name (e.g. all under some `Models/`), and only to a
     * project-wide `**` when they are genuinely scattered.
     *
     * @param  array<int, string>  $files  absolute file paths
     */
    protected function globForFiles(array $files, string $basePath): string
    {
        if ($files === []) {
            return '**';
        }

        $directories = array_values(array_unique(array_map(
            static fn (string $file): string => str_replace('\\', '/', dirname($file)),
            $files,
        )));

        $segmentLists = array_map(static fn (string $dir): array => explode('/', $dir), $directories);
        $common = [];

        foreach ($segmentLists[0] as $index => $segment) {
            foreach ($segmentLists as $list) {
                if (($list[$index] ?? null) !== $segment) {
                    break 2;
                }
            }

            $common[] = $segment;
        }

        $base = rtrim(str_replace('\\', '/', $basePath), '/');
        $commonDir = implode('/', $common);
        $relative = trim(str_starts_with($commonDir, $base) ? substr($commonDir, strlen($base)) : $commonDir, '/');

        if ($relative !== '') {
            return $relative.'/**';
        }

        $tails = array_unique(array_map(basename(...), $directories));

        return count($tails) === 1 ? '**/'.reset($tails).'/**' : '**';
    }
}
