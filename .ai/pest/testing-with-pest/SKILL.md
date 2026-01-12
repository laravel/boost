---
name: testing-with-pest
description: Testing PHP applications with Pest. Use when writing tests, creating test suites, using datasets, mocking dependencies, or setting up testing infrastructure.
---

# Testing with Pest

## When to use this skill

Use this skill when the user asks about:
- Writing tests for Laravel applications
- Using Pest's expressive syntax
- Creating datasets for parameterized tests
- Mocking and stubbing dependencies
- Setting up test fixtures
- Running and filtering tests

## Basic Test Structure

Pest uses a clean, expressive syntax:

```php
test('it can create a user', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBeInt();
});

it('validates email format', function () {
    $response = $this->post('/register', [
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});
```

## Expectations

Pest provides fluent expectations:

```php
// Basic expectations
expect($value)->toBe('exact value');
expect($value)->toEqual('loose comparison');
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->toBeEmpty();
expect($value)->not->toBeEmpty();

// Type expectations
expect($user)->toBeInstanceOf(User::class);
expect($items)->toBeArray();
expect($count)->toBeInt();
expect($price)->toBeFloat();
expect($name)->toBeString();

// Array expectations
expect($array)->toHaveCount(3);
expect($array)->toContain('item');
expect($array)->toHaveKey('name');
expect($array)->toMatchArray(['name' => 'John']);

// String expectations
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
expect($string)->toMatch('/regex/');

// Chained expectations
expect($user)
    ->name->toBe('John')
    ->email->toContain('@')
    ->posts->toHaveCount(3);
```

## Datasets

Parameterized tests with datasets:

```php
dataset('emails', [
    'standard' => ['user@example.com'],
    'subdomain' => ['user@mail.example.com'],
    'plus addressing' => ['user+tag@example.com'],
]);

it('accepts valid emails', function (string $email) {
    expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
})->with('emails');

// Inline datasets
it('calculates correctly', function (int $a, int $b, int $expected) {
    expect($a + $b)->toBe($expected);
})->with([
    [1, 1, 2],
    [2, 3, 5],
    [10, 5, 15],
]);

// Named parameters
it('formats names', function (string $first, string $last, string $expected) {
    expect("{$first} {$last}")->toBe($expected);
})->with([
    ['first' => 'John', 'last' => 'Doe', 'expected' => 'John Doe'],
    ['first' => 'Jane', 'last' => 'Smith', 'expected' => 'Jane Smith'],
]);
```

## Lifecycle Hooks

Setup and teardown:

```php
beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    // Cleanup after each test
});

beforeAll(function () {
    // Run once before all tests in this file
});

afterAll(function () {
    // Run once after all tests in this file
});
```

## HTTP Tests

Testing Laravel routes:

```php
it('shows the homepage', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Welcome');
});

it('creates a post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/posts', [
            'title' => 'My Post',
            'content' => 'Content here',
        ])
        ->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', [
        'title' => 'My Post',
        'user_id' => $user->id,
    ]);
});

it('requires authentication', function () {
    $this->post('/posts', ['title' => 'Test'])
        ->assertRedirect('/login');
});

it('validates input', function () {
    $this->actingAs(User::factory()->create())
        ->post('/posts', [])
        ->assertSessionHasErrors(['title', 'content']);
});
```

## Mocking

Mock dependencies and facades:

```php
use Mockery;

it('sends welcome email', function () {
    Mail::fake();

    $user = User::factory()->create();

    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('charges the customer', function () {
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('charge')
        ->once()
        ->with(1000, 'tok_visa')
        ->andReturn(true);

    $this->app->instance(PaymentGateway::class, $gateway);

    // Test code that uses PaymentGateway
});

// Using Pest's mock helper
it('mocks dependencies', function () {
    $mock = $this->mock(ExternalService::class, function ($mock) {
        $mock->shouldReceive('fetch')->andReturn(['data']);
    });

    // Test code
});
```

## Database Testing

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates users in database', function () {
    User::factory()->count(3)->create();

    $this->assertDatabaseCount('users', 3);
});

it('soft deletes posts', function () {
    $post = Post::factory()->create();

    $post->delete();

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
});
```

## Groups and Filtering

Organize tests with groups:

```php
it('is a slow test', function () {
    // ...
})->group('slow');

it('requires external service', function () {
    // ...
})->group('integration', 'external');

// Run specific groups
// pest --group=slow
// pest --exclude-group=slow
```

## Skipping Tests

```php
it('needs implementation', function () {
    // ...
})->todo();

it('only runs on CI', function () {
    // ...
})->skip(fn () => ! env('CI'), 'Only runs on CI');

it('requires redis', function () {
    // ...
})->skipOnWindows();
```

## Higher Order Tests

Concise test syntax:

```php
it('has a name')
    ->expect(fn () => new User(['name' => 'John']))
    ->name->toBe('John');

it('can be admin')
    ->expect(fn () => User::factory()->admin()->create())
    ->is_admin->toBeTrue();
```

## Architecture Tests

Test code architecture:

```php
arch('controllers have correct suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models extend base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('no debugging statements')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('strict types everywhere')
    ->expect('App')
    ->toUseStrictTypes();
```

## Running Tests

```bash
# Run all tests
pest

# Run specific file
pest tests/Feature/PostTest.php

# Run specific test
pest --filter="creates a post"

# Run with coverage
pest --coverage

# Run in parallel
pest --parallel

# Stop on first failure
pest --stop-on-failure

# Run only dirty tests (changed files)
pest --dirty
```

## Best Practices

1. **One assertion per test** - Keep tests focused
2. **Use factories** - Create test data with factories
3. **Name tests clearly** - Describe the behavior being tested
4. **Use datasets** - Avoid duplicate test code
5. **Isolate tests** - Each test should be independent
6. **Mock external services** - Don't hit real APIs in tests
7. **Use `RefreshDatabase`** - Reset database between tests
