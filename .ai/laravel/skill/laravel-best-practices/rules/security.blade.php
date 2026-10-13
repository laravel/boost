@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Security Best Practices

## Control Mass Assignment

Define `$fillable` for models populated from request-derived arrays, or guard attributes by a consistent convention. Models guard everything by default; `$guarded = []` opts out, so never combine it with untrusted request data. Mass-assignment protection limits which attributes `create()`, `fill()`, and `update()` set; it neither validates values nor authorizes the operation.

## Authorize Protected Actions

Use policies, gates, or form request authorization for permission-dependent actions. Authentication does not establish permission, and validation is not authorization.

```php
Gate::authorize('update', $post);
```

In a form request: `return $this->user()?->can('update', $this->route('post')) ?? false;`. Intentionally public actions need no extra check.

## Bind Query Parameters

Use Eloquent, the query builder, or explicit bindings (`whereRaw('LOWER(name) = ?', [$value])`); never interpolate untrusted values into SQL. Bindings protect values, not identifiers such as column names or sort directions, so map those to an allow-list.

## Escape Output in Its Context

Blade's `@{{ }}` HTML-escapes. Use `@{!! !!}` only for content sanitized for that exact context; HTML, URL, JavaScript, and CSS escape differently. Never use `@{!! $user->bio !!}` for untrusted content.

## Apply CSRF Protection

Include `@@csrf` in state-changing Blade forms under the `web` middleware. Routes excluded from CSRF verification, such as webhooks, need their own authenticity check. Inertia apps typically rely on Axios echoing the `XSRF-TOKEN` cookie as `X-XSRF-TOKEN`; confirm this for other HTTP clients. Do not disable CSRF to fix a token mismatch.

## Rate Limit Sensitive Endpoints

Throttle login, password recovery, verification messages, and expensive or abuse-prone API routes via `RateLimiter::for()` and `throttle:name` middleware. Pick the key deliberately: IP alone punishes users behind shared networks, while account alone enables targeted lockout, so combine them (`email|ip`). Rate limiting does not replace authentication, authorization, or upstream DoS protection.

## Validate and Store Uploads Safely

Validate content type, size, and dimensions where relevant, e.g. `['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']`. `mimes` guesses the type from file contents and ignores the user-supplied extension; `extensions` checks only the extension and is not enough alone.

Store with `->store('avatars')` so Laravel generates the filename, and keep untrusted files outside publicly executable locations. Public files may need image re-encoding, content-disposition headers, and blocking of active formats.

## Keep Secrets Out of Code

Do not commit populated env files or hard-code credentials. Read env only in config files and use `config()` elsewhere so config caching works. See the configuration rules for encrypted env files and secret stores.

## Audit Dependencies

Run `{{ $assist->composerCommand('audit') }}` regularly and in CI, and triage findings by exploitability.

## Encrypt Sensitive Attributes

Use the `encrypted` cast for recoverable secrets and add them to `$hidden` to omit them from array and JSON output. Hidden attributes remain readable in PHP, and encryption does not replace access control. Encrypted values cannot be queried meaningfully and need a `TEXT` or larger column.
