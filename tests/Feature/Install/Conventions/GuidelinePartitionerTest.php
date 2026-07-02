<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\GuidelinePartitioner;

it('offers directory-specific guidelines as path-scoped boost-guideline candidates', function (): void {
    $base = fixture('conventions/guidelines-app');

    $detections = (new GuidelinePartitioner($base.'/rules'))->detect();

    $byId = $detections->keyBy('id');

    expect($detections->pluck('id')->all())->toContain('guideline:eloquent', 'guideline:testing');
    expect($detections->pluck('id')->all())->not->toContain('guideline:style');

    $eloquent = $byId->get('guideline:eloquent');
    expect($eloquent->glob)->toBe('app/Models/**');
    expect($eloquent->title)->toBe('Eloquent Best Practices');
    expect($eloquent->provenance)->toBe(Detection::PROVENANCE_BOOST_GUIDELINE);
    expect($eloquent->note)->toContain('_Boost guideline, scoped to `app/Models/**`._');

    expect($byId->get('guideline:testing')->glob)->toBe('tests/**');
});

it('emits nothing when the guideline directory is absent', function (): void {
    $base = fixture('conventions/guidelines-app');

    $detections = (new GuidelinePartitioner($base.'/missing'))->detect();

    expect($detections)->toBeEmpty();
});
