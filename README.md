<p align="center">
    <img alt="Boost Logo Dark Mode" src="/art/boost-light-mode.svg#gh-light-mode-only"/>
    <img alt="Boost Logo Dark Mode" src="/art/boost-dark-mode.svg#gh-dark-mode-only"/>
</p>

<p align="center">
<a href="https://github.com/laravel/boost/actions"><img src="https://github.com/laravel/boost/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/dt/laravel/boost?v=1" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/v/laravel/boost?v=1" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/l/laravel/boost?v=1" alt="License"></a>
</p>

## Introduction

Laravel Boost accelerates AI-assisted development by providing the essential context and structure that AI needs to generate high-quality, Laravel-specific code.

## Official Documentation

Documentation for Laravel Boost can be found on the [Laravel website](https://laravel.com/docs/boost).

## Optional Streamable HTTP MCP Transport

By default, Laravel Boost exposes its MCP server over **stdio** via the `php artisan boost:mcp` command. This is the recommended transport for most local setups and remains unchanged.

Some MCP clients do not keep stdio servers alive reliably and may appear to "disconnect" Boost after periods of inactivity. For those clients, Boost can also expose the same MCP server over HTTP (Streamable HTTP) using `laravel/mcp`'s web transport.

The HTTP transport is **disabled by default**.

### Enabling

Publish the Boost config (if you have not already) and set the env vars:

```env
BOOST_MCP_WEB_ENABLED=true
BOOST_MCP_WEB_PATH=/_boost/mcp
```

Then serve the application normally with your usual local web stack (Herd, Valet, Sail, `nginx`/`php-fpm`, or `php artisan serve`). The MCP client should be pointed at the application URL plus the configured path, for example:

```
http://my-app.test/_boost/mcp
```

### Configuration

The full config block (in `config/boost.php`) looks like:

```php
'mcp' => [
    'web' => [
        'enabled'    => env('BOOST_MCP_WEB_ENABLED', false),
        'path'       => env('BOOST_MCP_WEB_PATH', '/_boost/mcp'),
        'middleware' => [],
    ],
],
```

You may attach project-appropriate middleware to the HTTP MCP route, for example:

```php
'middleware' => ['auth:sanctum'],
```

The stdio transport is unaffected by this setting and continues to work as before.

### Security

> **Warning** Laravel Boost exposes development tooling that can inspect your application, database, logs, routes, configuration, and other sensitive local development information. Do **not** expose the HTTP MCP endpoint on a public network without authentication and network-level protections.

The HTTP transport is intended for trusted local development environments. If you must expose it beyond your local machine, protect it with appropriate middleware (such as `auth:sanctum`) and network-level controls. Do not enable it in production.

## Contributing

Thank you for considering contributing to Boost! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/boost/security/policy) on how to report security vulnerabilities.

## License

Laravel Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
