---
title: Deployment Best Practices
impact: HIGH
tags: deployment, production, optimization, cloud
---

## Deployment Best Practices

### Use Laravel Cloud

Use Laravel Cloud for fully-managed, auto-scaling deployments. It's built by the Laravel team, offering managed compute, databases, caches, and object storage — fine-tuned to work seamlessly with the framework.

### Run `php artisan optimize` on Deploy

Cache config, events, routes, and views in a single command. This should be part of every deployment pipeline.

```bash
php artisan optimize
```

This runs `config:cache`, `event:cache`, `route:cache`, and `view:cache` together.

### Restart Long-Running Services After Deploy

Queue workers, Horizon, Reverb, and Octane hold stale code in memory. Restart them after every deploy.

```bash
php artisan reload
```

On Laravel Cloud this is handled automatically. On self-managed servers, use a process monitor (Supervisor) to auto-restart terminated workers.

### Disable Debug Mode in Production

Never expose debug info to users. `APP_DEBUG=true` in production leaks sensitive config values.

```env
APP_DEBUG=false
```

### Use the Health Route

Laravel ships with a `/up` health check route. Use it for load balancers, uptime monitors, and orchestration systems like Kubernetes.

```php
->withRouting(
    health: '/up',
)
```

Listen for `DiagnosingHealth` to add custom checks (database, cache, external services).

### Restart Queue Workers on Deploy

Queue workers are long-lived processes. They won't pick up code changes until restarted.

```bash
php artisan queue:restart
```

For Horizon:

```bash
php artisan horizon:terminate
```
