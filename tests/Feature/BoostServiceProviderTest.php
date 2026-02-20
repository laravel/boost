<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Laravel\Boost\Boost;
use Laravel\Boost\BoostManager;
use Laravel\Boost\BoostServiceProvider;

beforeEach(function (): void {
    $this->refreshApplication();
    Config::set('logging.channels.browser');
});

describe('boost.enabled configuration', function (): void {
    it('does not boot boost when disabled', function (): void {
        Config::set('boost.enabled', false);
        app()->detectEnvironment(fn (): string => 'local');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        $this->artisan('list')->expectsOutputToContain('boost:install');
    });

    it('boots boost when enabled in local environment', function (): void {
        Config::set('boost.enabled', true);
        app()->detectEnvironment(fn (): string => 'local');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        expect(app()->bound(Laravel\Roster\Roster::class))->toBeTrue()
            ->and(config('logging.channels.browser'))->not->toBeNull();
    });

    it('does not override an existing browser log channel', function (): void {
        Config::set('boost.enabled', true);
        Config::set('logging.channels.browser', [
            'driver' => 'daily',
            'path' => storage_path('logs/custom-browser.log'),
        ]);
        app()->detectEnvironment(fn (): string => 'local');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        expect(config('logging.channels.browser.driver'))->toBe('daily')
            ->and(config('logging.channels.browser.path'))->toBe(storage_path('logs/custom-browser.log'));
    });
});

describe('environment restrictions', function (): void {
    it('does not boot boost in production even when enabled', function (): void {
        Config::set('boost.enabled', true);
        Config::set('app.debug', false);
        app()->detectEnvironment(fn (): string => 'production');

        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));

        expect(config('logging.channels.browser'))->toBeNull();
    });

    describe('testing environment', function (): void {
        it('does not boot boost when debug is false', function (): void {
            Config::set('boost.enabled', true);
            Config::set('app.debug', false);
            app()->detectEnvironment(fn (): string => 'testing');

            $provider = new BoostServiceProvider(app());
            $provider->register();
            $provider->boot(app('router'));

            expect(config('logging.channels.browser'))->toBeNull();
        });

        it('does not boot boost when debug is true', function (): void {
            Config::set('boost.enabled', true);
            Config::set('app.debug', true);
            app()->detectEnvironment(fn (): string => 'testing');

            $provider = new BoostServiceProvider(app());
            $provider->register();
            $provider->boot(app('router'));

            expect(config('logging.channels.browser'))->toBeNull();
        });
    });
});

describe('BoostManager registration', function (): void {
    beforeEach(function (): void {
        Config::set('boost.enabled', true);
        app()->detectEnvironment(fn (): string => 'local');
        $provider = new BoostServiceProvider(app());
        $provider->register();
        $provider->boot(app('router'));
    });

    it('registers BoostManager in the container', function (): void {
        expect(app()->bound(BoostManager::class))->toBeTrue()
            ->and(app(BoostManager::class))->toBeInstanceOf(BoostManager::class);
    });

    it('registers BoostManager as a singleton', function (): void {
        Config::set('boost.enabled', true);
        $instance1 = app(BoostManager::class);
        $instance2 = app(BoostManager::class);

        expect($instance1)->toBe($instance2);
    });

    it('binds Boost facade to the same BoostManager instance', function (): void {
        Config::set('boost.enabled', true);
        $containerInstance = app(BoostManager::class);
        $facadeInstance = Boost::getFacadeRoot();

        expect($facadeInstance)->toBe($containerInstance);
    });
});
