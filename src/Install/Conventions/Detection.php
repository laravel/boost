<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

final class Detection
{
    public const PROVENANCE_INFERRED = 'inferred';

    public const PROVENANCE_BOOST_GUIDELINE = 'boost-guideline';

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $note,
        public readonly string $glob,
        public readonly float $confidence,
        public readonly bool $preselected = false,
        public readonly string $provenance = self::PROVENANCE_INFERRED,
    ) {}

    public function withPreselected(bool $preselected): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->note,
            $this->glob,
            $this->confidence,
            $preselected,
            $this->provenance,
        );
    }

    /**
     * Package-shipped rules carry a `package:vendor/name` provenance so the UI can attribute them and
     * so the inspector can keep them opt-in (never auto-preselected) per §3.2.
     */
    public static function packageProvenance(string $package): string
    {
        return 'package:'.$package;
    }

    public function isInferred(): bool
    {
        return $this->provenance === self::PROVENANCE_INFERRED;
    }
}
