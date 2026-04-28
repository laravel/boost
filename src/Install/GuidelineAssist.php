<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Install\Assists\Inertia;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Finder;

class GuidelineAssist
{
    /** @var array<string, string> */
    protected array $enumPaths = [];

    public function __construct(public Roster $roster, public GuidelineConfig $config, public ?Collection $skills = null)
    {
        $this->skills ??= collect();
        $this->enumPaths = $this->discover();
    }

    /**
     * @return array<string, string> - className, absolutePath
     */
    public function enums(): array
    {
        return $this->enumPaths;
    }

    /**
     * Discover all enum files in the application directory.
     *
     * @return array<string, string>
     */
    protected function discover(): array
    {
        $appPath = app_path();

        if (! is_dir($appPath)) {
            return [];
        }

        $enums = [];

        $finder = Finder::create()
            ->in($appPath)
            ->files()
            ->name('/[A-Z].*\.php$/');

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $code = file_get_contents($path);

            if ($code === false) {
                continue;
            }

            if (stripos($code, 'enum') === false) {
                continue;
            }

            $tokens = token_get_all($code);

            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_ENUM) {
                    $className = app()->getNamespace().str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $file->getRelativePathname()
                    );
                    $enums[$className] = $path;

                    break;
                }
            }
        }

        return $enums;
    }

    public function enumContents(): string
    {
        if ($this->enumPaths === []) {
            return '';
        }

        $path = current($this->enumPaths);

        if (! is_file($path)) {
            return '';
        }

        return file_get_contents($path) ?: '';
    }

    public function inertia(): Inertia
    {
        return new Inertia($this->roster);
    }

    public function supportsPintAgentFormatter(): bool
    {
        return $this->roster->usesVersion(Packages::PINT, '1.27.0', '>=');
    }

    public function hasPackage(Packages $package): bool
    {
        return $this->roster->packages()->contains(
            fn ($pkg): bool => $pkg->package() === $package
        );
    }

    public function nodePackageManager(): string
    {
        return ($this->roster->nodePackageManager() ?? NodePackageManager::NPM)->value;
    }

    protected function detectedNodePackageManager(): string
    {
        return $this->nodePackageManager();
    }

    public function nodePackageManagerCommand(string $command): string
    {
        $npmExecutable = config('boost.executable_paths.npm');

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
        $composerExecutable = config('boost.executable_paths.composer');

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
        $vendorBinPrefix = config('boost.executable_paths.vendor_bin');

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
        $phpExecutable = config('boost.executable_paths.php');

        if ($phpExecutable !== null) {
            return "{$phpExecutable} artisan";
        }

        return $this->config->usesSail
            ? Sail::artisanCommand()
            : 'php artisan';
    }

    public function sailBinaryPath(): string
    {
        return Sail::binaryPath();
    }

    public function appPath(string $path = ''): string
    {
        return ltrim(Str::after(app_path($path), base_path()), DIRECTORY_SEPARATOR);
    }

    public function hasSkillsEnabled(): bool
    {
        return $this->config->hasSkills;
    }

    public function hasMcpEnabled(): bool
    {
        return $this->config->hasMcp;
    }
}
