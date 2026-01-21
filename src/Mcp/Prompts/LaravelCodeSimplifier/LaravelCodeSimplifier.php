<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\LaravelCodeSimplifier;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class LaravelCodeSimplifier extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'laravel-code-simplifier';

    protected string $title = 'laravel_code_simplifier';

    protected string $description = 'Simplifies and refines PHP/Laravel code for clarity, consistency, and maintainability while preserving all functionality. Focuses on recently modified code unless instructed otherwise.';

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/laravel-code-simplifier.blade.php');

        return Response::text($content);
    }
}
