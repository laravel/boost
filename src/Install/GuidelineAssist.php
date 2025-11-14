<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Database\Eloquent\Model;
use Laravel\Boost\Install\Assists\Inertia;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Roster;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

class GuidelineAssist
{
    /** @var array<string, string> */
    protected array $modelPaths = [];

    protected array $controllerPaths = [];

    protected array $enumPaths = [];

    protected static array $classes = [];

    public function __construct(public Roster $roster, public GuidelineConfig $config)
    {
        $this->modelPaths = $this->discover(fn ($reflection): bool => ($reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract()));
        $this->controllerPaths = $this->discover(fn (ReflectionClass $reflection): bool => (stripos($reflection->getName(), 'controller') !== false || stripos($reflection->getNamespaceName(), 'controller') !== false));
        $this->enumPaths = $this->discover(fn ($reflection) => $reflection->isEnum());
    }

    /**
     * @return array<string, string> - className, absolutePath
     */
    public function models(): array
    {
        return $this->modelPaths;
    }

    /**
     * @return array<string, string> - className, absolutePath
     */
    public function controllers(): array
    {
        return $this->controllerPaths;
    }

    /**
     * @return array<string, string> - className, absolutePath
     */
    public function enums(): array
    {
        return $this->enumPaths;
    }

    /**
     * Discover all Eloquent models in the application.
     *
     * @return array<string, string>
     */
    protected function discover(callable $cb): array
    {
        $classes = [];
        $appPath = app_path();

        if (! is_dir($appPath)) {
            return ['app-path-isnt-a-directory' => $appPath];
        }

        if (self::$classes === []) {
            $finder = Finder::create()
                ->in($appPath)
                ->files()
                ->name('/[A-Z].*\.php$/');

            foreach ($finder as $file) {
                $relativePath = $file->getRelativePathname();
                $namespace = app()->getNamespace();
                $className = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $relativePath
                );

                try {
                    $path = $appPath.DIRECTORY_SEPARATOR.$relativePath;

                    if (! $this->fileHasClassLike($path)) {
                        continue;
                    }

                    if (class_exists($className, false)) {
                        self::$classes[$className] = $path;
                    }
                } catch (Throwable) {
                    // Ignore exceptions and errors from class loading/reflection
                }
            }
        }

        foreach (self::$classes as $className => $path) {
            if ($cb(new ReflectionClass($className))) {
                $classes[$className] = $path;
            }
        }

        return $classes;
    }

    public function fileHasClassLike(string $path): bool
    {
        static $cache = [];

        if (isset($cache[$path])) {
            return $cache[$path];
        }

        $code = file_get_contents($path);
        if ($code === false) {
            return $cache[$path] = false;
        }

        if (stripos($code, 'class') === false
            && stripos($code, 'interface') === false
            && stripos($code, 'trait') === false
            && stripos($code, 'enum') === false) {
            return $cache[$path] = false;
        }

        $tokens = token_get_all($code);
        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                return $cache[$path] = true;
            }
        }

        return $cache[$path] = false;
    }

    public function shouldEnforceStrictTypes(): bool
    {
        if ($this->modelPaths === []) {
            return false;
        }

        return str_contains(
            file_get_contents(current($this->modelPaths)),
            'strict_types=1'
        );
    }

    public function enumContents(): string
    {
        if ($this->enumPaths === []) {
            return '';
        }

        return file_get_contents(current($this->enumPaths));
    }

    public function inertia(): Inertia
    {
        return new Inertia($this->roster);
    }

    public function nodePackageManager(): string
    {
        return ($this->roster->nodePackageManager() ?? NodePackageManager::NPM)->value;
    }

    public function nodePackageManagerCommand(string $command): string
    {
        $manager = $this->nodePackageManager();
        $nodePackageManagerCommand = $this->config->usesSail
            ? Sail::nodePackageManagerCommand($manager)
            : $manager;

        return "{$nodePackageManagerCommand} {$command}";
    }

    public function artisanCommand(string $command): string
    {
        return "{$this->artisan()} {$command}";
    }

    public function composerCommand(string $command): string
    {
        $composerCommand = $this->config->usesSail
            ? Sail::composerCommand()
            : 'composer';

        return "{$composerCommand} {$command}";
    }

    public function binCommand(string $command): string
    {
        return $this->config->usesSail
            ? Sail::binCommand().$command
            : "vendor/bin/{$command}";
    }

    public function artisan(): string
    {
        return $this->config->usesSail
            ? Sail::artisanCommand()
            : 'php artisan';
    }

    public function sailBinaryPath(): string
    {
        return Sail::BINARY_PATH;
    }
}
