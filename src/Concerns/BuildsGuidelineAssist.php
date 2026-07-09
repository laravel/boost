<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Roster\Roster;

/**
 * @property Roster $roster
 * @property GuidelineConfig $config
 */
trait BuildsGuidelineAssist
{
    protected function buildGuidelineAssist(): GuidelineAssist
    {
        return new GuidelineAssist($this->roster, $this->config);
    }
}
