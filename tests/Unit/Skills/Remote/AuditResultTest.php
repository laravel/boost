<?php

declare(strict_types=1);

use Laravel\Boost\Skills\Remote\AuditResult;
use Laravel\Boost\Skills\Remote\Risk;

it('stores optional alerts and analyzedAt', function (): void {
    $result = new AuditResult(
        partner: 'socket',
        risk: Risk::Low,
        alerts: 5,
        analyzedAt: '2025-01-01T00:00:00Z',
    );

    expect($result->alerts)->toBe(5)
        ->and($result->analyzedAt)->toBe('2025-01-01T00:00:00Z');
});

it('defaults optional fields to null', function (): void {
    $result = new AuditResult(partner: 'ath', risk: Risk::Safe);

    expect($result->alerts)->toBeNull()
        ->and($result->analyzedAt)->toBeNull();
});
