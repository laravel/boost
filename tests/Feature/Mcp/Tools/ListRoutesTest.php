<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\ListRoutes;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    Route::get('/admin/dashboard', fn (): string => 'admin dashboard')->name('admin.dashboard');

    Route::post('/admin/users', fn (): string => 'admin users')->name('admin.users.store');

    Route::get('/user/profile', fn (): string => 'user profile')->name('user.profile');

    Route::get('/api/two-factor/enable', fn (): string => 'two-factor enable')->name('two-factor.enable');

    Route::get('/api/v1/posts', fn (): string => 'posts')->name('api.posts.index');

    Route::put('/api/v1/posts/{id}', fn ($id): string => 'update post')->name('api.posts.update');
});

test('it returns list of routes without filters', function (): void {
    $tool = new ListRoutes;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('GET|HEAD', 'admin.dashboard', 'user.profile');
});

test('it sanitizes name parameter wildcards and filters correctly', function (): void {
    $tool = new ListRoutes;

    $response = $tool->handle(new Request(['name' => '*admin*']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard', 'admin.users.store')
        ->and($response)->not->toolTextContains('user.profile', 'two-factor.enable');

    $response = $tool->handle(new Request(['name' => '*two-factor*']));

    expect($response)->toolTextContains('two-factor.enable')
        ->and($response)->not->toolTextContains('admin.dashboard', 'user.profile');

    $response = $tool->handle(new Request(['name' => '*api*']));

    expect($response)->toolTextContains('api.posts.index', 'api.posts.update')
        ->and($response)->not->toolTextContains('admin.dashboard', 'user.profile');

});

test('it sanitizes method parameter wildcards and filters correctly', function (): void {
    $tool = new ListRoutes;

    $response = $tool->handle(new Request(['method' => 'GET*POST']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains("ERROR  Your application doesn't have any routes matching the given criteria.");

    $response = $tool->handle(new Request(['method' => '*GET*']));

    expect($response)->toolTextContains('admin.dashboard', 'user.profile', 'api.posts.index')
        ->and($response)->not->toolTextContains('admin.users.store');

    $response = $tool->handle(new Request(['method' => '*POST*']));

    expect($response)->toolTextContains('admin.users.store')
        ->and($response)->not->toolTextContains('admin.dashboard');
});

test('it handles edge cases and empty results correctly', function (): void {
    $tool = new ListRoutes;

    $response = $tool->handle(new Request(['name' => '*']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard', 'user.profile', 'two-factor.enable');

    $response = $tool->handle(new Request(['name' => '*nonexistent*']));

    expect($response)->toolTextContains("ERROR  Your application doesn't have any routes matching the given criteria.");

    $response = $tool->handle(new Request(['name' => '']));

    expect($response)->toolTextContains('admin.dashboard', 'user.profile');
});

test('it handles multiple parameters with wildcard sanitization', function (): void {
    $tool = new ListRoutes;

    $response = $tool->handle(new Request([
        'name' => '*admin*',
        'method' => '*GET*',
    ]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard')
        ->and($response)->not->toolTextContains('admin.users.store', 'user.profile');

    $response = $tool->handle(new Request([
        'name' => '*user*',
        'method' => '*POST*',
    ]));

    expect($response)->toolTextContains('admin.users.store');
});

test('it handles the original problematic wildcard case', function (): void {
    $tool = new ListRoutes;

    $response = $tool->handle(new Request(['name' => '*two-factor*']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('two-factor.enable');
});
