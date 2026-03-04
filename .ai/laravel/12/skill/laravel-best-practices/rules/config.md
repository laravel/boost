---
title: Configuration Best Practices
impact: HIGH
tags: config, env, secrets, environment
---

## Configuration Best Practices

### env() Only in Config Files

Direct `env()` calls return `null` when config is cached.

**Incorrect:**

```php
$key = env('API_KEY');
```

**Correct:**

```php
// config/services.php
'key' => env('API_KEY'),

// Application code
$key = config('services.key');
```

### Use Encrypted Env or External Secrets

Never store production secrets in plain `.env` files in version control.

**Incorrect:**

```bash
# .env committed to repo or shared in Slack
STRIPE_SECRET=sk_live_abc123
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI
```

**Correct:**

```bash
php artisan env:encrypt --env=production --readable
php artisan env:decrypt --env=production
```

For cloud deployments, prefer the platform's native secret store (AWS Secrets Manager, Vault, etc.) and inject at runtime.

### Use App::environment() for Environment Checks

**Incorrect:**

```php
if (env('APP_ENV') === 'production') {
```

**Correct:**

```php
if (app()->isProduction()) {
// or
if (App::environment('production')) {
```

### Use Constants and Language Files

Use config values, language files, and class constants instead of hardcoded strings.

**Incorrect:**

```php
public function isNormal(): bool
{
    return $this->type === 'normal';
}

return back()->with('message', 'Your article has been added!');
```

**Correct:**

```php
public function isNormal(): bool
{
    return $this->type === self::TYPE_NORMAL;
}

return back()->with('message', __('app.article_added'));
```
