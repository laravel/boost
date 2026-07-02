<?php

declare(strict_types=1);
use Illuminate\Console\Command;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Install\Conventions\Contracts\Detector;

arch('strict types')
    ->expect('Laravel\Boost')
    ->toUseStrictTypes();

arch('no debugging')
    ->expect(['dd', 'dump', 'var_dump', 'die', 'ray'])
    ->not->toBeUsed();

arch('commands')
    ->expect('Laravel\Boost\Commands')
    ->toExtend(Command::class)
    ->toHaveSuffix('Command');

arch('convention detectors')
    ->expect('Laravel\Boost\Install\Conventions\Detectors')
    ->toImplement(Detector::class)
    ->toHaveSuffix('Detector');

arch('no direct env calls')
    ->expect('env')
    ->not->toBeUsedIn('Laravel\Boost')
    ->ignoring([
        BoostServiceProvider::class,
    ]);

arch('tests')
    ->expect('Tests')
    ->not->toBeUsedIn('Laravel\Boost');
