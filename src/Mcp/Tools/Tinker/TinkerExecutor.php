<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\Tinker;

use Illuminate\Support\Env;
use Laravel\Tinker\ClassAliasAutoloader;
use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use ReflectionClass;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class TinkerExecutor
{
    protected Shell $shell;

    protected BufferedOutput $output;

    protected ?ClassAliasAutoloader $loader = null;

    /**
     * Create a new TinkerExecutor instance.
     */
    public function __construct()
    {
        $config = new Configuration;
        $config->setUpdateCheck(Checker::NEVER);
        $config->setRawOutput(true);

        $this->shell = new Shell($config);
        $this->output = new BufferedOutput;
        $this->shell->setOutput($this->output);

        $this->loader = $this->registerClassAliasAutoloader();
    }

    /**
     * Execute PHP code and return the result.
     *
     * @return array<string, mixed>
     */
    public function execute(string $code): array
    {
        $code = str_replace(['<?php', '?>'], '', $code);

        ob_start();

        try {
            $result = $this->shell->execute($code, true);

            $echoOutput = ob_get_contents();
            $shellOutput = $this->output->fetch();

            $combinedOutput = trim($echoOutput.$shellOutput);

            $response = [
                'result' => $result,
                'type' => gettype($result),
            ];

            if ($combinedOutput !== '') {
                $response['output'] = $combinedOutput;
            }

            if (is_object($result)) {
                $response['class'] = $result::class;
            }

            return $response;
        } catch (Throwable $throwable) {
            return [
                'error' => $throwable->getMessage(),
                'type' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ];
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Clean up resources.
     */
    public function __destruct()
    {
        $this->loader?->unregister();
    }

    /**
     * Register the class alias autoloader for Laravel.
     */
    protected function registerClassAliasAutoloader(): ?ClassAliasAutoloader
    {
        $vendorPath = Env::get('COMPOSER_VENDOR_DIR');

        if ($vendorPath === null) {
            $reflection = new ReflectionClass(\Composer\Autoload\ClassLoader::class);
            $vendorPath = dirname($reflection->getFileName(), 2);
        }

        $classMapPath = $vendorPath.'/composer/autoload_classmap.php';

        if (! file_exists($classMapPath)) {
            return null;
        }

        $appConfig = app('config');

        return ClassAliasAutoloader::register(
            $this->shell,
            $classMapPath,
            $appConfig->get('tinker.alias', []),
            $appConfig->get('tinker.dont_alias', [])
        );
    }
}
