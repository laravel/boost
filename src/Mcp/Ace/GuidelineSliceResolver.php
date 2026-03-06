<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

use Laravel\Boost\Install\GuidelineComposer;

class GuidelineSliceResolver
{
    public function __construct(protected GuidelineComposer $composer) {}

    public function resolve(ContextSlice $slice): SliceResult
    {
        if ($slice->guidelineKey === null) {
            return new SliceResult($slice->id, '', isError: true);
        }

        try {
            $content = $this->composer->resolveSlice($slice->guidelineKey);

            if ($content === '') {
                return new SliceResult($slice->id, "Guideline '{$slice->guidelineKey}' not found or empty.", isError: true);
            }

            return new SliceResult($slice->id, $content);
        } catch (\Throwable $e) {
            return new SliceResult($slice->id, "Error resolving guideline '{$slice->guidelineKey}': {$e->getMessage()}", isError: true);
        }
    }
}
