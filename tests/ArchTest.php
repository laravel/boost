<?php

declare(strict_types=1);

arch('strict types')
    ->expect('Laravel\Boost')
    ->toUseStrictTypes();

arch('no debugging')
    ->expect(['dd', 'dump', 'var_dump', 'die', 'ray'])
    ->not->toBeUsed();

arch('commands')
    ->expect('Laravel\Boost\Commands')
    ->toExtend(\Illuminate\Console\Command::class)
    ->toHaveSuffix('Command');

arch('no direct env calls')
    ->expect('env')
    ->not->toBeUsedIn('Laravel\Boost')
    ->ignoring([
        \Laravel\Boost\BoostServiceProvider::class,
    ]);

arch('tests')
    ->expect('Tests')
    ->not->toBeUsedIn('Laravel\Boost');
