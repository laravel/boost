<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\SkillWriter;

it('throws when one or more skills fail to sync', function (): void {
    $reflection = new ReflectionClass(InstallCommand::class);
    $command = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('ensureSkillSyncSucceeded');

    expect(fn () => $method->invoke($command, [
        'successful-skill' => SkillWriter::SUCCESS,
        'first-failed-skill' => SkillWriter::FAILED,
        'updated-skill' => SkillWriter::UPDATED,
        'second-failed-skill' => SkillWriter::FAILED,
    ]))->toThrow(
        RuntimeException::class,
        'Failed to sync skills: first-failed-skill, second-failed-skill'
    );
});
