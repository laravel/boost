<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Support\Composer;
use Laravel\Boost\Support\Npm;

class ThirdPartyPackage
{
    public function __construct(
        public readonly string $name,
        public readonly bool $hasGuidelines,
        public readonly bool $hasSkills,
    ) {
        //
    }

    /**
     * Discover all third-party packages with boost features.
     *
     * @return Collection<string, ThirdPartyPackage>
     */
    public static function discover(): Collection
    {
        $withGuidelines = self::guidelineDirectories();
        $withSkills = self::skillDirectories();

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
                ),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function guidelineDirectories(): array
    {
        return self::rejectFirstParty(array_merge(
            Composer::packagesDirectoriesWithBoostGuidelines(),
            Npm::packagesDirectoriesWithBoostGuidelines()
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function skillDirectories(): array
    {
        return self::rejectFirstParty(array_merge(
            Composer::packagesDirectoriesWithBoostSkills(),
            Npm::packagesDirectoriesWithBoostSkills()
        ));
    }

    /**
     * @param  array<string, string>  $directories
     * @return array<string, string>
     */
    private static function rejectFirstParty(array $directories): array
    {
        return array_filter(
            $directories,
            fn (string $name): bool => ! Composer::isFirstPartyPackage($name) && ! Npm::isFirstPartyPackage($name),
            ARRAY_FILTER_USE_KEY
        );
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
        return "{$this->name} ({$this->featureLabel()})";
    }
}
