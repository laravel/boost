<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\ListRoutes;

beforeEach(function () {
    Route::get('/admin/dashboard', function () {
        return 'admin dashboard';
    })->name('admin.dashboard');

    Route::post('/admin/users', function () {
        return 'admin users';
    })->name('admin.users.store');

    Route::get('/user/profile', function () {
        return 'user profile';
    })->name('user.profile');

    Route::get('/api/two-factor/enable', function () {
        return 'two-factor enable';
    })->name('two-factor.enable');

    Route::get('/api/v1/posts', function () {
        return 'posts';
    })->name('api.posts.index');

    Route::put('/api/v1/posts/{id}', function ($id) {
        return 'update post';
    })->name('api.posts.update');
});

test('it returns list of routes without filters', function () {
    $tool = new ListRoutes;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('GET|HEAD', 'admin.dashboard', 'user.profile');
});

test('it sanitizes name parameter wildcards and filters correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*admin*']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard', 'admin.users.store')
        ->and($result)->not->toolTextContains('user.profile', 'two-factor.enable');

    $result = $tool->handle(['name' => '*two-factor*']);

    expect($result)->toolTextContains('two-factor.enable')
        ->and($result)->not->toolTextContains('admin.dashboard', 'user.profile');

    $result = $tool->handle(['name' => '*api*']);

    expect($result)->toolTextContains('api.posts.index', 'api.posts.update')
        ->and($result)->not->toolTextContains('admin.dashboard', 'user.profile');

});

test('it sanitizes method parameter wildcards and filters correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['method' => 'GET*POST']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('ERROR  Your application doesn\'t have any routes matching the given criteria.');

    $result = $tool->handle(['method' => '*GET*']);

    expect($result)->toolTextContains('admin.dashboard', 'user.profile', 'api.posts.index')
        ->and($result)->not->toolTextContains('admin.users.store');

    $result = $tool->handle(['method' => '*POST*']);

    expect($result)->toolTextContains('admin.users.store')
        ->and($result)->not->toolTextContains('admin.dashboard');
});

test('it handles edge cases and empty results correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard', 'user.profile', 'two-factor.enable');

    $result = $tool->handle(['name' => '*nonexistent*']);

    expect($result)->toolTextContains('ERROR  Your application doesn\'t have any routes matching the given criteria.');

    $result = $tool->handle(['name' => '']);

    expect($result)->toolTextContains('admin.dashboard', 'user.profile');
});

test('it handles multiple parameters with wildcard sanitization', function () {
    $tool = new ListRoutes;

    $result = $tool->handle([
        'name' => '*admin*',
        'method' => '*GET*',
    ]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('admin.dashboard')
        ->and($result)->not->toolTextContains('admin.users.store', 'user.profile');

    $result = $tool->handle([
        'name' => '*user*',
        'method' => '*POST*',
    ]);

    expect($result)->toolTextContains('admin.users.store');
});

test('it handles the original problematic wildcard case', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*two-factor*']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('two-factor.enable');
});
