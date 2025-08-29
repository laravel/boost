<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class GuidelineConfig
{
    public bool $enforceTests = false;

    public bool $laravelStyle = false;

    public bool $caresAboutLocalization = false;

    public bool $hasAnApi = false;

    public bool $usesLaravelModules = false;
}
