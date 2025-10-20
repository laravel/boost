<?php

declare(strict_types=1);

use Laravel\Roster\Roster;
use Laravel\Boost\Install\GuidelineAssist;

beforeEach(function () {
    $lockFiles = [
        base_path('package-lock.json'),
        base_path('pnpm-lock.yaml'),
        base_path('yarn.lock'),
        base_path('bun.lock'),
    ];

    foreach ($lockFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

afterEach(function () {
    $lockFiles = [
        base_path('package-lock.json'),
        base_path('pnpm-lock.yaml'),
        base_path('yarn.lock'),
        base_path('bun.lock'),
    ];

    foreach ($lockFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('it detects npm when package-lock.json exists', function () {
    file_put_contents(base_path('package-lock.json'), '{}');

    $assist = new GuidelineAssist(Roster::scan(base_path()));
    expect($assist->nodePackageManager())->toBe('npm');
});

test('it detects pnpm when pnpm-lock.yaml exists', function () {
    file_put_contents(base_path('pnpm-lock.yaml'), 'lockfileVersion: 6.0');

    $assist = new GuidelineAssist(Roster::scan(base_path()));
    expect($assist->nodePackageManager())->toBe('pnpm');
});

test('it detects yarn when yarn.lock exists', function () {
    file_put_contents(base_path('yarn.lock'), '# This is yarn lock file');

    $assist = new GuidelineAssist(Roster::scan(base_path()));
    expect($assist->nodePackageManager())->toBe('yarn');
});

test('it detects bun when bun.lock exists', function () {
    file_put_contents(base_path('bun.lock'), 'bun lock file');

    $assist = new GuidelineAssist(Roster::scan(base_path()));
    expect($assist->nodePackageManager())->toBe('bun');
});

test('it defaults to npm when no lock files exist', function () {
    $assist = new GuidelineAssist(Roster::scan(base_path()));
    expect($assist->nodePackageManager())->toBe('npm');
});
