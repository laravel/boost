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

        if ($this->shouldInject($response)) {
            $originalView = $response->original ?? null;
            $injectedContent = $this->injectScript($response->getContent());
            $response->setContent($injectedContent);

            if ($originalView instanceof View && property_exists($response, 'original')) {
                $response->original = $originalView;
            }
        }

        return $response;
    }

    private function shouldInject(Response $response): bool
    {
        if (str_contains($response->headers->get('content-type', ''), 'html') === false) {
            return false;
        }

        $content = $response->getContent();
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

        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $head = $dom->getElementsByTagName('head')->item(0);
        $body = $dom->getElementsByTagName('body')->item(0);

        $scriptNode = $dom->createDocumentFragment();
        $scriptNode->appendXML($script);

        if ($head) {
            $head->appendChild($scriptNode);
        } elseif ($body) {
            $body->insertBefore($scriptNode, $body->firstChild);
        } else {
            // Fallback for documents without <head> or <body>
            $html = $dom->getElementsByTagName('html')->item(0);
            if ($html) {
                $html->appendChild($scriptNode);
            } else {
                return $content . $script;
            }
        }

        return $dom->saveHTML();
    }
}
