<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class BrowserLogsRouteRegistrationTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('boost.browser_logs_watcher', false);
    }

    public function test_browser_logs_route_is_not_registered_when_the_watcher_is_disabled(): void
    {
        // With the watcher off the `browser` log channel is never defined, so a
        // registered route would 500 on Log::channel('browser') instead of 404ing.
        $this->postJson('/_boost/browser-logs', ['logs' => []])->assertNotFound();
    }
}
