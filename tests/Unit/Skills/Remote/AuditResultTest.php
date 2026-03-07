<?php

declare(strict_types=1);

use Laravel\Boost\Skills\Remote\AuditResult;

it('returns correct risk weight', function (string $risk, int $expectedWeight): void {
    $result = new AuditResult(partner: 'ath', risk: $risk);

    expect($result->riskWeight())->toBe($expectedWeight);
})->with([
    ['critical', 5],
    ['high', 4],
    ['medium', 3],
    ['low', 2],
    ['safe', 1],
    ['unknown', 0],
    ['something-else', 0],
]);

it('returns correct risk label', function (string $risk, string $expectedLabel): void {
    $result = new AuditResult(partner: 'ath', risk: $risk);

    expect($result->riskLabel())->toBe($expectedLabel);
})->with([
    ['critical', 'Critical Risk'],
    ['high', 'High Risk'],
    ['medium', 'Med Risk'],
    ['low', 'Low Risk'],
    ['safe', 'Safe'],
    ['unknown', 'Unknown'],
    ['something-else', 'Unknown'],
]);

it('returns correct risk color', function (string $risk, string $expectedColor): void {
    $result = new AuditResult(partner: 'ath', risk: $risk);

    expect($result->riskColor())->toBe($expectedColor);
})->with([
    ['critical', 'red'],
    ['high', 'red'],
    ['medium', 'yellow'],
    ['low', 'green'],
    ['safe', 'green'],
    ['unknown', 'gray'],
]);

it('stores optional alerts and analyzedAt', function (): void {
    $result = new AuditResult(
        partner: 'socket',
        risk: 'low',
        alerts: 5,
        analyzedAt: '2025-01-01T00:00:00Z',
    );

    expect($result->alerts)->toBe(5)
        ->and($result->analyzedAt)->toBe('2025-01-01T00:00:00Z');
});

it('defaults optional fields to null', function (): void {
    $result = new AuditResult(partner: 'ath', risk: 'safe');

    expect($result->alerts)->toBeNull()
        ->and($result->analyzedAt)->toBeNull();
});
