<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Laravel\Boost\BoostServiceProvider;

beforeEach(function () {
    $this->refreshApplication();
    Config::set('logging.channels.browser', null);
});

describe('boost.enabled configuration', function () {
    it('does not boot boost when disabled', function () {
        Config::set('boost.enabled', false);
        app()->detectEnvironment(fn () => 'local');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        $this->artisan('list')->expectsOutputToContain('boost:install');
    });

    it('boots boost when enabled in local environment', function () {
        Config::set('boost.enabled', true);
        app()->detectEnvironment(fn () => 'local');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        expect(app()->bound(Laravel\Roster\Roster::class))->toBeTrue();
        expect(config('logging.channels.browser'))->not->toBeNull();
    });
});

describe('environment restrictions', function () {
    it('does not boot boost in production even when enabled', function () {
        Config::set('boost.enabled', true);
        Config::set('app.debug', false);
        app()->detectEnvironment(fn () => 'production');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        expect(config('logging.channels.browser'))->toBeNull();
    });

    describe('testing environment', function () {
        it('does not boot boost when debug is false', function () {
            Config::set('boost.enabled', true);
            Config::set('app.debug', false);
            app()->detectEnvironment(fn () => 'testing');

            $provider = new BoostServiceProvider(app());
            $provider->register();
            $provider->boot(app('router'));

            expect(config('logging.channels.browser'))->toBeNull();
        });

        it('does not boot boost when debug is true', function () {
            Config::set('boost.enabled', true);
            Config::set('app.debug', true);
            app()->detectEnvironment(fn () => 'testing');

            $provider = new BoostServiceProvider(app());
            $provider->register();
            $provider->boot(app('router'));

            expect(config('logging.channels.browser'))->toBeNull();
        });
    });
});
