<?php

declare(strict_types=1);

namespace Laravel\Boost\Guidelines\Remote;

class RemoteGuideline
{
    public function __construct(
        public string $name,
        public string $repo,
        public string $path,
        public string $relativePath,
    ) {
        //
    }
}
