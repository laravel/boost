---
title: Architecture Best Practices
impact: MEDIUM
tags: architecture, actions, dependency-injection, interfaces, packages, concurrency, storage
---

## Architecture Best Practices

### Single-Purpose Action Classes

Extract discrete business operations into invokable Action classes.

```php
class CreateOrderAction
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    public function execute(array $data): Order
    {
        $order = Order::create($data);
        $this->inventory->reserve($order);

        return $order;
    }
}
```

### Use Dependency Injection

Always use constructor injection. Avoid `app()` or `resolve()` inside classes.

**Incorrect:**

```php
$user = new User;
$user->create($data);
```

**Correct:**

```php
public function __construct(
    private User $user,
) {}

$this->user->create($data);
```

### Code to Interfaces

Depend on contracts at system boundaries (payment gateways, notification channels, external APIs) for testability and swappability.

**Incorrect (concrete dependency):**

```php
class OrderService
{
    public function __construct(private StripeGateway $gateway) {}
}
```

**Correct (interface dependency):**

```php
interface PaymentGateway
{
    public function charge(int $amount, string $customerId): PaymentResult;
}

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}
}
```

Bind in a service provider:

```php
$this->app->bind(PaymentGateway::class, StripeGateway::class);
```

### Default Sort by Descending

When no explicit order is specified, sort by `id` or `created_at` descending. Explicit ordering prevents cross-database inconsistencies between MySQL and Postgres.

**Incorrect:**

```php
$posts = Post::paginate();
```

**Correct:**

```php
$posts = Post::latest()->paginate();
```

### Use Atomic Locks for Race Conditions

Prevent race conditions with `Cache::lock()` or `lockForUpdate()`.

```php
Cache::lock('order-processing-'.$order->id, 10)->block(5, function () use ($order) {
    $order->process();
});

// Or at query level
$product = Product::where('id', $id)->lockForUpdate()->first();
```

### Use mb_* String Functions

When no Laravel helper exists, prefer `mb_strlen`, `mb_strtolower`, etc. for UTF-8 safety. Standard PHP string functions count bytes, not characters.

**Incorrect:**

```php
strlen('José');          // 5 (bytes, not characters)
strtolower('MÜNCHEN');  // 'mÜnchen' — fails on multibyte
```

**Correct:**

```php
mb_strlen('José');             // 4 (characters)
mb_strtolower('MÜNCHEN');     // 'münchen'

// Prefer Laravel's Str helpers when available
Str::length('José');          // 4
Str::lower('MÜNCHEN');        // 'münchen'
```

### Plan for Ephemeral Storage

On Laravel Cloud/Vapor, local disk is ephemeral — files disappear between deployments. Use S3 for persistent storage.

**Incorrect:**

```php
$request->file('avatar')->store('avatars', 'local');
```

**Correct:**

```php
$request->file('avatar')->store('avatars', 's3');

// For large files, use signed upload URLs
$url = Storage::disk('s3')->temporaryUploadUrl(
    'uploads/'.Str::uuid().'.pdf',
    now()->addMinutes(5),
);
```

### Convention Over Configuration

Follow Laravel conventions. Don't override defaults unnecessarily.

**Incorrect:**

```php
class Customer extends Model
{
    protected $table = 'Customer';
    protected $primaryKey = 'customer_id';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_customer', 'customer_id', 'role_id');
    }
}
```

**Correct:**

```php
class Customer extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```
