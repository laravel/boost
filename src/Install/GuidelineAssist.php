<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Database\Eloquent\Model;
use Laravel\Boost\Install\Assists\Inertia;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
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
        $paths = $this->config->discoveryPaths !== []
            ? $this->config->discoveryPaths
            : [app_path()];
        $cacheKey = md5(implode('|', $paths));

        if (! collect($paths)->every(fn ($path): bool => is_dir($path))) {
            return ['invalid-discovery-paths' => implode(',', $paths)];
        }

        if (! isset(self::$classes[$cacheKey])) {
            self::$classes[$cacheKey] = [];
            $finder = Finder::create()
                ->in($paths)
                ->files()
                ->name('/[A-Z].*\.php$/');

            foreach ($finder as $file) {
                try {
                    $path = $file->getRealPath();
                    if (! $path) {
                        continue;
                    }

                    if (! $this->fileHasClassLike($path)) {
                        continue;
                    }

                    $className = $this->classNameFromFile($path);

                    if ($className && class_exists($className)) {
                        self::$classes[$cacheKey][$className] = $path;
                    }
                } catch (Throwable) {
                    // Ignore exceptions and errors from class loading/reflection
                }
            }
        }

        foreach (self::$classes[$cacheKey] as $className => $path) {
            if ($cb(new ReflectionClass($className))) {
                $classes[$className] = $path;
            }
        }

        return $classes;
    }

    protected function classNameFromFile(string $path): ?string
    {
        $code = file_get_contents($path);

        if ($code === false) {
            return null;
        }

        $namespace = null;
        $class = null;

        $tokens = token_get_all($code);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                        $namespace .= $tokens[$j][1];

                        continue;
                    }

                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                }
            }

            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_CLASS, T_ENUM], true)) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }

        }

        if (! $class) {
            return null;
        }

        return $namespace ? "{$namespace}\\{$class}" : $class;
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

    public function supportsPintAgentFormatter(): bool
    {
        return $this->roster->usesVersion(Packages::PINT, '1.27.0', '>=');
    }

    protected function detectedNodePackageManager(): string
    {
        return ($this->roster->nodePackageManager() ?? NodePackageManager::NPM)->value;
    }

    public function nodePackageManagerCommand(string $command): string
    {
        $npmExecutable = config('boost.executables.npm');

        if ($npmExecutable !== null) {
            return "{$npmExecutable} {$command}";
        }

        if ($this->config->usesSail) {
            return Sail::nodePackageManagerCommand($this->detectedNodePackageManager())." {$command}";
        }

        return "{$this->detectedNodePackageManager()} {$command}";
    }

    public function artisanCommand(string $command): string
    {
        return "{$this->artisan()} {$command}";
    }

    public function composerCommand(string $command): string
    {
        $composerExecutable = config('boost.executables.composer');

        if ($composerExecutable !== null) {
            return "{$composerExecutable} {$command}";
        }

        if ($this->config->usesSail) {
            return Sail::composerCommand()." {$command}";
        }

        return "composer {$command}";
    }

    public function binCommand(string $command): string
    {
        $vendorBinPrefix = config('boost.executables.vendor_bin');

        if ($vendorBinPrefix !== null) {
            return "{$vendorBinPrefix}{$command}";
        }

        if ($this->config->usesSail) {
            return Sail::binCommand().$command;
        }

        return "vendor/bin/{$command}";
    }

    public function artisan(): string
    {
        $phpExecutable = config('boost.executables.php');

        if ($phpExecutable !== null) {
            return "{$phpExecutable} artisan";
        }

        return $this->config->usesSail
            ? Sail::artisanCommand()
            : 'php artisan';
    }

    public function sailBinaryPath(): string
    {
        return Sail::BINARY_PATH;
    }
}
