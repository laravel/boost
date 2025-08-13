<?php

declare(strict_types=1);

namespace Laravel\Boost\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Boost\Services\BrowserLogger;
use Symfony\Component\HttpFoundation\Response;

class InjectBoost
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if ($this->shouldInject($response->getContent())) {
            $originalView = $response->original ?? null;
            $injectedContent = $this->injectScript($response->getContent());
            $response->setContent($injectedContent);

            if ($originalView instanceof View && property_exists($response, 'original')) {
                $response->original = $originalView;
            }
        }

        return $response;
    }

    private function shouldInject(string|false $content): bool
    {
        // Check if there is content at all
        if ($content === false) {
            return false;
        }

        // Check if it's HTML
        if (! str_contains($content, '<html') && ! str_contains($content, '<head')) {
            return false;
        }

        // Check if already injected
        if (str_contains($content, 'browser-logger-active')) {
            return false;
        }

        return true;
    }

    private function injectScript(string $content): string
    {
        $script = BrowserLogger::getScript();

        // Try to inject before closing </head>
        if (str_contains($content, '</head>')) {
            return str_replace('</head>', $script."\n</head>", $content);
        }

        // Fallback: inject before closing </body>
        if (str_contains($content, '</body>')) {
            return str_replace('</body>', $script."\n</body>", $content);
        }

        return $content.$script;
    }
}
