<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Support\Composer;

class ThirdPartyPackage
{
    public function __construct(
        public readonly string $name,
        public readonly bool $hasGuidelines,
        public readonly bool $hasSkills,
        public readonly int $tokens = 0,
    ) {}

    /**
     * Discover all third-party packages with boost features.
     *
     * @return Collection<string, ThirdPartyPackage>
     */
    public static function discover(GuidelineComposer $guidelineComposer): Collection
    {
        $withGuidelines = Composer::packagesDirectoriesWithBoostGuidelines();
        $withSkills = Composer::packagesDirectoriesWithBoostSkills();

        $guidelineInfo = self::getThirdPartyGuidelineInfo($guidelineComposer);

        $allPackageNames = array_unique(array_merge(
            array_keys($withGuidelines),
            array_keys($withSkills)
        ));

        return collect($allPackageNames)
            ->mapWithKeys(fn (string $name): array => [
                $name => new self(
                    name: $name,
                    hasGuidelines: isset($withGuidelines[$name]),
                    hasSkills: isset($withSkills[$name]),
                    tokens: $guidelineInfo[$name]['tokens'] ?? 0,
                ),
            ]);
    }

    public function featureLabel(): string
    {
        return match (true) {
            $this->hasGuidelines && $this->hasSkills => 'guidelines, skills',
            $this->hasGuidelines => 'guideline',
            $this->hasSkills => 'skills',
            default => '',
        };
    }

    public function displayLabel(): string
    {
        $parts = [$this->name, "({$this->featureLabel()})"];

        if ($this->hasGuidelines && $this->tokens > 0) {
            $parts[] = "(~{$this->tokens} tokens)";
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, array{tokens: int}>
     */
    protected static function getThirdPartyGuidelineInfo(GuidelineComposer $guidelineComposer): array
    {
        return $guidelineComposer->guidelines()
            ->filter(fn (array $guideline): bool => $guideline['third_party'] === true)
            ->mapWithKeys(fn (array $guideline, string $name): array => [
                $name => [
                    'tokens' => (int) ($guideline['tokens'] ?? 0),
                ],
            ])
            ->toArray();
    }
}
