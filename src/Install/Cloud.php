<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class Cloud
{
    public function skillRepo(): string
    {
        return 'laravel/cloud-cli';
    }

    public function skillPath(): string
    {
        return 'skills';
    }

    public function skillName(): string
    {
        return 'deploying-laravel-cloud';
    }
}
