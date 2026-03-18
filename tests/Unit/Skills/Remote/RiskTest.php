<?php

declare(strict_types=1);

use Laravel\Boost\Skills\Remote\Risk;

it('returns correct weight', function (Risk $risk, int $expectedWeight): void {
    expect($risk->weight())->toBe($expectedWeight);
})->with([
    [Risk::Critical, 5],
    [Risk::High, 4],
    [Risk::Medium, 3],
    [Risk::Low, 2],
    [Risk::Safe, 1],
]);

it('returns correct label', function (Risk $risk, string $expectedLabel): void {
    expect($risk->label())->toBe($expectedLabel);
})->with([
    [Risk::Critical, 'Critical Risk'],
    [Risk::High, 'High Risk'],
    [Risk::Medium, 'Med Risk'],
    [Risk::Low, 'Low Risk'],
    [Risk::Safe, 'Safe'],
]);

it('returns correct color', function (Risk $risk, string $expectedColor): void {
    expect($risk->color())->toBe($expectedColor);
})->with([
    [Risk::Critical, 'red'],
    [Risk::High, 'red'],
    [Risk::Medium, 'yellow'],
    [Risk::Low, 'green'],
    [Risk::Safe, 'green'],
]);

it('can be created from valid string values', function (string $value, Risk $expected): void {
    expect(Risk::tryFrom($value))->toBe($expected);
})->with([
    ['critical', Risk::Critical],
    ['high', Risk::High],
    ['medium', Risk::Medium],
    ['low', Risk::Low],
    ['safe', Risk::Safe],
]);

it('returns null for unknown string values', function (string $value): void {
    expect(Risk::tryFrom($value))->toBeNull();
})->with([
    'unknown',
    'something-else',
    '',
]);
