---
name: testing-with-phpunit
description: Testing PHP applications with PHPUnit. Use when writing unit tests, feature tests, mocking dependencies, or setting up testing infrastructure.
---

# Testing with PHPUnit

## When to use this skill

Use this skill when the user asks about:
- Writing tests for Laravel applications using PHPUnit
- Creating unit and feature tests
- Data providers for parameterized tests
- Mocking and stubbing
- Test assertions

## Basic Test Structure

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/posts', [
                'title' => 'My Post',
                'content' => 'Content here',
            ]);

        $response->assertRedirect('/posts');
        $this->assertDatabaseHas('posts', ['title' => 'My Post']);
    }
}
```

## Assertions

Common PHPUnit assertions:

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison
$this->assertNotEquals($expected, $actual);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotNull($value);

// Arrays
$this->assertCount(3, $array);
$this->assertContains('item', $array);
$this->assertArrayHasKey('key', $array);
$this->assertEmpty($array);

// Strings
$this->assertStringContainsString('needle', $haystack);
$this->assertStringStartsWith('prefix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Types
$this->assertInstanceOf(User::class, $object);
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertIsInt($value);
```

## Data Providers

Parameterized tests:

```php
/**
 * @dataProvider validEmailProvider
 */
public function test_accepts_valid_emails(string $email): void
{
    $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
}

public static function validEmailProvider(): array
{
    return [
        'standard' => ['user@example.com'],
        'subdomain' => ['user@mail.example.com'],
        'plus addressing' => ['user+tag@example.com'],
    ];
}

/**
 * @dataProvider calculationProvider
 */
public function test_calculations(int $a, int $b, int $expected): void
{
    $this->assertEquals($expected, $a + $b);
}

public static function calculationProvider(): array
{
    return [
        [1, 1, 2],
        [2, 3, 5],
        [10, 5, 15],
    ];
}
```

## Setup and Teardown

```php
class UserTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        // Cleanup
        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Run once before all tests
    }

    public static function tearDownAfterClass(): void
    {
        // Run once after all tests
        parent::tearDownAfterClass();
    }
}
```

## HTTP Tests

```php
public function test_homepage_loads(): void
{
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Welcome');
}

public function test_create_post(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/posts', [
            'title' => 'My Post',
            'content' => 'Content',
        ]);

    $response->assertRedirect('/posts');
    $this->assertDatabaseHas('posts', ['title' => 'My Post']);
}

public function test_validation_errors(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/posts', []);

    $response->assertSessionHasErrors(['title', 'content']);
}

public function test_json_api(): void
{
    $response = $this->postJson('/api/posts', [
        'title' => 'API Post',
    ]);

    $response->assertStatus(201)
        ->assertJson(['title' => 'API Post']);
}
```

## Mocking

```php
use Mockery;

public function test_sends_notification(): void
{
    $mock = Mockery::mock(NotificationService::class);
    $mock->shouldReceive('send')
        ->once()
        ->with('user@example.com', 'Welcome!')
        ->andReturn(true);

    $this->app->instance(NotificationService::class, $mock);

    // Test code that uses NotificationService
}

public function test_with_mock_method(): void
{
    $this->mock(PaymentGateway::class, function ($mock) {
        $mock->shouldReceive('charge')
            ->once()
            ->andReturn(true);
    });

    // Test code
}
```

## Faking Laravel Services

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

public function test_sends_email(): void
{
    Mail::fake();

    // Trigger email...

    Mail::assertSent(WelcomeEmail::class);
}

public function test_dispatches_event(): void
{
    Event::fake();

    // Trigger event...

    Event::assertDispatched(OrderPlaced::class);
}

public function test_queues_job(): void
{
    Queue::fake();

    // Trigger job...

    Queue::assertPushed(ProcessOrder::class);
}

public function test_stores_file(): void
{
    Storage::fake('local');

    // Upload file...

    Storage::disk('local')->assertExists('file.txt');
}
```

## Database Testing

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_user(): void
    {
        User::factory()->count(3)->create();

        $this->assertDatabaseCount('users', 3);
    }

    public function test_soft_delete(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_missing_record(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'none@example.com']);
    }
}
```

## Grouping Tests

```php
/**
 * @group slow
 * @group integration
 */
public function test_slow_operation(): void
{
    // ...
}
```

Run specific groups:
```bash
php artisan test --group=slow
php artisan test --exclude-group=slow
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific file
php artisan test tests/Feature/PostTest.php

# Run specific method
php artisan test --filter=test_user_can_create_post

# Stop on first failure
php artisan test --stop-on-failure

# Run in parallel
php artisan test --parallel

# With coverage
php artisan test --coverage
```

## Best Practices

1. **One assertion focus per test** - Test one behavior
2. **Use factories** - Create test data consistently
3. **Descriptive test names** - `test_user_can_create_post`
4. **Use RefreshDatabase** - Clean state between tests
5. **Mock external services** - Don't call real APIs
6. **Test edge cases** - Empty inputs, boundaries
7. **Keep tests fast** - Mock slow operations
