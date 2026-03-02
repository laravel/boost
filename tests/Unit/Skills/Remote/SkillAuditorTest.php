<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Laravel\Boost\Skills\Remote\AuditResult;
use Laravel\Boost\Skills\Remote\SkillAuditor;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('returns audit results for skills', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response([
            'skill-one' => [
                'ath' => ['risk' => 'safe', 'analyzedAt' => '2025-01-01T00:00:00Z'],
                'socket' => ['risk' => 'low', 'alerts' => 2, 'analyzedAt' => '2025-01-01T00:00:00Z'],
                'snyk' => ['risk' => 'safe', 'analyzedAt' => '2025-01-01T00:00:00Z'],
            ],
        ]),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results)->toHaveKey('skill-one')
        ->and($results['skill-one'])->toHaveCount(3)
        ->and($results['skill-one'][0])->toBeInstanceOf(AuditResult::class)
        ->and($results['skill-one'][0]->partner)->toBe('ath')
        ->and($results['skill-one'][0]->risk)->toBe('safe')
        ->and($results['skill-one'][1]->partner)->toBe('socket')
        ->and($results['skill-one'][1]->risk)->toBe('low')
        ->and($results['skill-one'][1]->alerts)->toBe(2)
        ->and($results['skill-one'][2]->partner)->toBe('snyk')
        ->and($results['skill-one'][2]->risk)->toBe('safe');
});

it('returns audit results for multiple skills', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response([
            'skill-one' => [
                'ath' => ['risk' => 'safe', 'analyzedAt' => '2025-01-01T00:00:00Z'],
            ],
            'skill-two' => [
                'ath' => ['risk' => 'high', 'analyzedAt' => '2025-01-01T00:00:00Z'],
            ],
        ]),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one', 'skill-two']);

    expect($results)->toHaveCount(2)
        ->and($results)->toHaveKey('skill-one')
        ->and($results)->toHaveKey('skill-two')
        ->and($results['skill-two'][0]->risk)->toBe('high');
});

it('sends correct query parameters', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response([]),
    ]);

    $auditor = new SkillAuditor;
    $auditor->audit('owner/repo', ['skill-one', 'skill-two']);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'source=owner%2Frepo')
        && str_contains((string) $request->url(), 'skills=skill-one%2Cskill-two'));
});

it('returns empty array on network failure', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response(null, 500),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results)->toBe([]);
});

it('returns empty array on connection timeout', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out'),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results)->toBe([]);
});

it('returns empty array on malformed response', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response('not json', 200, ['Content-Type' => 'text/plain']),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results)->toBe([]);
});

it('skips partner entries without risk field', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response([
            'skill-one' => [
                'ath' => ['risk' => 'safe', 'analyzedAt' => '2025-01-01T00:00:00Z'],
                'socket' => ['analyzedAt' => '2025-01-01T00:00:00Z'],
            ],
        ]),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results['skill-one'])->toHaveCount(1)
        ->and($results['skill-one'][0]->partner)->toBe('ath');
});

it('skips non-array partner data', function (): void {
    Http::fake([
        'add-skill.vercel.sh/audit*' => Http::response([
            'skill-one' => 'invalid',
        ]),
    ]);

    $auditor = new SkillAuditor;
    $results = $auditor->audit('owner/repo', ['skill-one']);

    expect($results)->toBe([]);
});
