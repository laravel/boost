<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

interface PostProcessGuidelines
{
    /**
     * Post-process the generated guidelines markdown.
     */
    public function postProcessGuidelines(string $markdown): string;
}
