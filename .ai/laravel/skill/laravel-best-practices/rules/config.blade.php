@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Configuration Best Practices

## Read Environment Variables in Configuration Files

Call `env()` only in `config/*.php`. Once configuration is cached, `.env` is not loaded and `env()` returns `null` elsewhere. Application code reads `config('services.key')`.

## Protect Production Secrets

Never commit plaintext production secrets. Laravel can encrypt the environment file so the encrypted form is safe to store:

```bash
{{ $assist->artisanCommand('env:encrypt --env=production --readable') }}
{{ $assist->artisanCommand('env:decrypt --env=production') }}
```

For hosted deployments, prefer the platform's secret store (AWS Secrets Manager, Vault) and inject secrets at runtime.

## Check the Environment with `App::environment()`

Use `app()->isProduction()` or `App::environment('production')`, never `env('APP_ENV')`.

## Name Repeated Domain Values

Use an enum or class constant for a value that is repeated or belongs to a constrained set; a one-off literal does not need one. In a localized application put user-facing strings in language files and use `__()`; plain literals are fine when the app intentionally has one language.
