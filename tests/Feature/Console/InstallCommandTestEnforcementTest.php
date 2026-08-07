<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Console\InstallCommand;

it('does not crash when the test-listing process is signaled', function (): void {
    $projectPath = sys_get_temp_dir().'/boost_'.uniqid();
    File::ensureDirectoryExists($projectPath.'/vendor/bin');
    File::put($projectPath.'/vendor/bin/phpunit', '');
    File::put($projectPath.'/artisan', '<?php posix_kill(posix_getpid(), 5);');
    app()->setBasePath($projectPath);

    $reflection = new ReflectionClass(InstallCommand::class);
    $command = $reflection->newInstanceWithoutConstructor();
    $result = $reflection->getMethod('determineTestEnforcement')->invoke($command);

    File::deleteDirectory($projectPath);

    expect($result)->toBeFalse();
})->skipOnWindows();
