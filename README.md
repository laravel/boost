<p align="center"><img src="/art/logo.svg" alt="Logo Laravel Boost"></p>

<p align="center">
<a href="https://github.com/laravel/boost/actions"><img src="https://github.com/laravel/boost/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/dt/boost" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/v/boost" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/l/boost" alt="License"></a>
</p>

## Introduction
Laravel Boost gives you a jump-start with AI assisted coding by making it simple to add everything you need to help AI make good choices.

Core features:
- MCP server with 15+ tools
- Composable AI guidelines for ecosystem packages
- Documentation API with built-in MCP tool

Other features:
- Browser logs streamed to `log/storage/browser.log`

> [!IMPORTANT]
> Boost is in Beta and will be updated frequently.

## Installation

Add the package
```bash
composer require laravel/boost --dev
```

Install the MCP server & guidelines
```bash
./artisan boost:install
```

## Current MCP Tools

| Name                       | Notes                                                        |
| -------------------------- | ------------------------------------------------------------ |
| Application Info           | Shares PHP & Laravel versions, database engine, list of ecosystem packages with versions, and Eloquent models. |
| Browser Logs               | Read logs & errors from the browser                          |
| Database Connections       | List database connections, and the default                   |
| Database Query             |                                                              |
| Database Schema            |                                                              |
| Get Absolute Url           | Converts relative path to absolute so AI doesn't give you invalid URLs |
| Get Config                 | Get specific value from config using dot notation            |
| Last Error                 | From the log files                                           |
| List Artisan Commands      |                                                              |
| List Available Config Keys |                                                              |
| List Available Env Vars    | Keys only                                                    |
| List Routes                | Regular & folio routes are combined. Ability to filter routes too |
| Read Log Entries           | Last X entries                                               |
| Report Feedback            | Share Boost & Laravel AI feedback with the team              |
| Search Docs                | Use hosted API service to retrieve docs based on installed packages |
| Tinker                     | Run arbitrary code within the context of the project         |


## Adding your own AI guidelines

Add `.blade.php` files to `.ai/guidelines/*` in your project, and they'll be included as part of `boost:install`.

## Official Documentation

Documentation for Boost can be found on the [Laravel website](https://laravel.com/docs).

## Contributing

Thank you for considering contributing to Boost! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/boost/security/policy) on how to report security vulnerabilities.

## License

Laravel AI Assistant is open-sourced software licensed under the [MIT license](LICENSE.md).
