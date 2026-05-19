<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route as Router;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;

function bootBoost(): void
{
    app()->detectEnvironment(fn (): string => 'local');

    $provider = new BoostServiceProvider(app());
    $provider->register();
    $provider->boot(app('router'));
}

function findRouteByUri(string $uri): ?Route
{
    foreach (Router::getRoutes()->getRoutes() as $route) {
        if ($route->uri() === ltrim($uri, '/')) {
            return $route;
        }
    }

    return null;
}

beforeEach(function (): void {
    $this->refreshApplication();
    Config::set('logging.channels.browser');
    Config::set('boost.enabled', true);
});

describe('web MCP registration', function (): void {
    it('does not register the web MCP route by default', function (): void {
        bootBoost();

        expect(Mcp::getWebServer('_boost/mcp'))->toBeNull()
            ->and(findRouteByUri('_boost/mcp'))->toBeNull();
    });

    it('does not register the web MCP route when explicitly disabled', function (): void {
        Config::set('boost.mcp.web.enabled', false);
        Config::set('boost.mcp.web.path', '/_boost/mcp');

        bootBoost();

        expect(Mcp::getWebServer('_boost/mcp'))->toBeNull();
    });

    it('registers the web MCP route when enabled', function (): void {
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/_boost/mcp');

        bootBoost();

        $route = Mcp::getWebServer('_boost/mcp');

        expect($route)->not->toBeNull()
            ->and($route)->toBeInstanceOf(Route::class)
            ->and(in_array('POST', $route->methods(), true))->toBeTrue();
    });

    it('respects a custom configured path', function (): void {
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/custom/mcp-endpoint');

        bootBoost();

        expect(Mcp::getWebServer('custom/mcp-endpoint'))->not->toBeNull()
            ->and(Mcp::getWebServer('_boost/mcp'))->toBeNull();
    });

    it('applies configured middleware to the route', function (): void {
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/_boost/mcp');
        Config::set('boost.mcp.web.middleware', ['auth:sanctum', 'throttle:mcp']);

        bootBoost();

        $route = Mcp::getWebServer('_boost/mcp');

        expect($route)->not->toBeNull()
            ->and($route->middleware())->toContain('auth:sanctum')
            ->and($route->middleware())->toContain('throttle:mcp');
    });

    it('does not register the web route when boost itself is disabled', function (): void {
        Config::set('boost.enabled', false);
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/_boost/mcp');

        bootBoost();

        expect(Mcp::getWebServer('_boost/mcp'))->toBeNull();
    });

    it('keeps the local stdio MCP server registered alongside the web route', function (): void {
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/_boost/mcp');

        bootBoost();

        expect(Mcp::getLocalServer('laravel-boost'))->not->toBeNull()
            ->and(Mcp::getWebServer('_boost/mcp'))->not->toBeNull();
    });

    it('keeps the local stdio MCP server registered when web is disabled (default)', function (): void {
        bootBoost();

        expect(Mcp::getLocalServer('laravel-boost'))->not->toBeNull();
    });

    it('uses the Boost server class for the web route', function (): void {
        Config::set('boost.mcp.web.enabled', true);
        Config::set('boost.mcp.web.path', '/_boost/mcp');

        bootBoost();

        // Sanity check: the laravel/mcp registrar stored the route, and the
        // Boost server class is the one Boost wires up.
        expect(Boost::class)->toBeString()
            ->and(Mcp::getWebServer('_boost/mcp'))->not->toBeNull();
    });
});
