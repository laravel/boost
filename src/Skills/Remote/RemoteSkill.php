<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

class RemoteSkill
{
    public function __construct(public string $name, public string $repo, public string $path)
    {
        //
    }
}
