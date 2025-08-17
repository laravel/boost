<?php

declare(strict_types=1);

namespace Laravel\Boost;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Middleware\InjectBoost;
use Laravel\Mcp\Server\Facades\Mcp;
use Laravel\Roster\Roster;

class BoostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/boost.php',
            'boost'
        );

        $this->app->singleton(Roster::class, function () {
            $lockFiles = [
                base_path('composer.lock'),
                base_path('package-lock.json'),
                base_path('bun.lockb'),
                base_path('pnpm-lock.yaml'),
                base_path('yarn.lock'),
            ];

            $cacheKey = 'boost.roster.scan';
            $lastModified = max(array_map(fn ($path) => file_exists($path) ? filemtime($path) : 0, $lockFiles));

            $cached = cache()->get($cacheKey);
            if ($cached && isset($cached['timestamp']) && $cached['timestamp'] >= $lastModified) {
                return $cached['roster'];
            }

            $roster = Roster::scan(base_path());
            cache()->put($cacheKey, [
                'roster' => $roster,
                'timestamp' => time(),
            ], now()->addHours(24));

            return $roster;
        });
    }

    public function boot(Router $router): void
    {
        if (! config('boost.enabled', true)) {
            return;
        }

        // Only enable Boost on local environments
        if (! app()->environment(['local', 'testing']) && config('app.debug', false) !== true) {
            return;
        }

        // @phpstan-ignore-next-line
        Mcp::local('laravel-boost', Boost::class);

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerBrowserLogger();
        $this->callAfterResolving('blade.compiler', fn (BladeCompiler $bladeCompiler) => $this->registerBladeDirectives($bladeCompiler));
        $this->hookIntoResponses($router);
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/boost.php' => config_path('boost.php'),
            ], 'boost-config');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\StartCommand::class,
                Console\InstallCommand::class,
                Console\ExecuteToolCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        Route::post('/_boost/browser-logs', function (Request $request) {
            $logs = $request->input('logs', []);
            /** @var Logger $logger */
            $logger = Log::channel('browser');

            /**
             *  @var array{
             *      type: 'error'|'warn'|'info'|'log'|'table'|'window_error'|'uncaught_error'|'unhandled_rejection',
             *      timestamp: string,
             *      data: array,
             *      url:string,
             *      userAgent:string
             *  } $log */
            foreach ($logs as $log) {
                $logger->write(
                    level: self::mapJsTypeToPsr3Level($log['type']),
                    message: self::buildLogMessageFromData($log['data']),
                    context: [
                        'url' => $log['url'],
                        'user_agent' => $log['userAgent'] ?: null,
                        'timestamp' => $log['timestamp'] ?: now()->toIso8601String(),
                    ]
                );
            }

            return response()->json(['status' => 'logged']);
        })
            ->name('boost.browser-logs')
            ->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Build a string message for the log based on various input types. Single-dimensional, and multi:
     * "data": {"message":"Unhandled Promise Rejection","reason":{"name":"TypeError","message":"NetworkError when attempting to fetch resource.","stack":""}}]
     */
    private static function buildLogMessageFromData(array $data): string
    {
        $messages = [];

        foreach ($data as $value) {
            $messages[] = match (true) {
                is_array($value) => self::buildLogMessageFromData($value),
                is_string($value), is_numeric($value) => (string) $value,
                is_bool($value) => $value ? 'true' : 'false',
                is_null($value) => 'null',
                is_object($value) => json_encode($value),
                default => $value,
            };
        }

        return implode(' ', $messages);
    }

    private function registerBrowserLogger(): void
    {
        config([
            'logging.channels.browser' => [
                'driver' => 'single',
                'path' => storage_path('logs/browser.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],
        ]);
    }

    private function registerBladeDirectives(BladeCompiler $bladeCompiler): void
    {
        $bladeCompiler->directive('boostJs', fn () => '<?php echo \\Laravel\\Boost\\Services\\BrowserLogger::getScript(); ?>');
    }

    private static function mapJsTypeToPsr3Level(string $type): string
    {
        return match ($type) {
            'warn' => 'warning',
            'log', 'table' => 'debug',
            'window_error', 'uncaught_error', 'unhandled_rejection' => 'error',
            default => $type
        };
    }

    private function hookIntoResponses(Router $router): void
    {
        if (! config('boost.browser_logs_watcher', true)) {
            return;
        }

        $router->pushMiddlewareToGroup('web', InjectBoost::class);
    }
}
