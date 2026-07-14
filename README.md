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

## Browser Log Levels

Boost captures browser console output to provide debugging context. Use `BOOST_BROWSER_LOG_LEVELS` to reduce noisy logs:

```env
BOOST_BROWSER_LOG_LEVELS=warning
```

| Value | Captured browser events |
| --- | --- |
| `error` | `console.error()` and browser errors |
| `warning` | warnings and errors |
| `info` | info, warnings, and errors |
| `debug` | `console.log()`, `console.debug()`, `console.info()`, `console.warn()`, `console.error()`, and `console.table()` |

The default is `debug`, which preserves Boost's existing behavior by capturing all supported browser events. An empty value also preserves that default. To disable browser logging entirely, set `BOOST_BROWSER_LOGS_WATCHER=false`.

## Contributing

Thank you for considering contributing to Boost! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/boost/security/policy) on how to report security vulnerabilities.

## License

Laravel Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
