<p align="center"><img src="/art/boost.svg" alt="Logo Laravel Boost"></p>

<p align="center">
<a href="https://github.com/laravel/boost/actions"><img src="https://github.com/laravel/boost/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/dt/laravel/boost?v=1" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/v/laravel/boost?v=1" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/boost"><img src="https://img.shields.io/packagist/l/laravel/boost?v=1" alt="License"></a>
</p>

## Introduction

Laravel Boost accelerates AI-assisted development by providing the essential context and structure that AI needs to generate high-quality, Laravel-specific code.

At its foundation, Laravel Boost is an MCP server equipped with over 15 specialized tools designed to streamline AI-assisted coding workflows. The package includes composable AI guidelines specifically crafted for Laravel ecosystem packages, ensuring consistent and framework-appropriate code generation.

Boost also features a powerful Documentation API that combines a built-in MCP tool with an extensive knowledge base containing over 17,000 pieces of Laravel-specific information, all enhanced by semantic search capabilities using embeddings for precise, context-aware results.

> [!IMPORTANT]
> Laravel Boost is currently in beta and receives frequent updates as we refine features and expand capabilities.

## Installation

Laravel Boost can be installed via Composer:

```bash
composer require laravel/boost --dev
```

Next, install the MCP server and coding guidelines:

```bash
php artisan boost:install
```

Once Laravel Boost has been installed, you're ready to start coding with Cursor, Claude Code, or your AI agent of choice.

## Available MCP Tools

| Name                       | Notes                                                                                                          |
| -------------------------- |----------------------------------------------------------------------------------------------------------------|
| Application Info           | Read PHP & Laravel versions, database engine, list of ecosystem packages with versions, and Eloquent models    |
| Browser Logs               | Read logs and errors from the browser                                                                          |
| Database Connections       | Inspect available database connections, including the default connection                                       |
| Database Query             | Execute a query against the database                                                                           |
| Database Schema            | Read the database schema                                                                                       |
| Get Absolute URL           | Convert relative path URIs to absolute so agents generate valid URLs                                           |
| Get Config                 | Get a value from the configuration files using "dot" notation                                                  |
| Last Error                 | Read the last error from the application's log files                                                           |
| List Artisan Commands      | Inspect the available Artisan commands                                                                         |
| List Available Config Keys | Inspect the available configuration keys                                                                       |
| List Available Env Vars    | Inspect the available environment variable keys                                                                |
| List Routes                | Inspect the application's routes                                                                               |
| Read Log Entries           | Read the last N log entries                                                                                    |
| Report Feedback            | Share Boost & Laravel AI feedback with the team, just say "give Boost feedback: x, y, and z"                   |
| Search Docs                | Query the Laravel hosted documentation API service to retrieve documentation based on installed packages       |
| Tinker                     | Execute arbitrary code within the context of the application                                                   |

## Available AI Guidelines

Laravel Boost includes AI guidelines for the following packages and frameworks. The `core` guidelines provide generic, generalized advice to the AI for the given package that is applicable across all versions.

| Package | Versions Supported |
|---------|-------------------|
| Core & Boost | core |
| Laravel Framework | core, 10.x, 11.x, 12.x |
| Livewire | core, 2.x, 3.x |
| Filament | core, 4.x |
| Flux UI | core, free, pro |
| Herd | core |
| Inertia Laravel | core, 1.x, 2.x |
| Inertia React | core, 1.x, 2.x |
| Inertia Vue | core, 1.x, 2.x |
| Pest | core, 4.x |
| PHPUnit | core |
| Pint | core |
| Tailwind CSS | core, 3.x, 4.x |
| Livewire Volt | core |
| Laravel Folio | core |
| Enforce Tests | conditional |


## Available Documentation

| Package | Versions Supported |
|---------|-------------------|
| Laravel Framework | 10.x, 11.x, 12.x |
| Filament | 2.x, 3.x, 4.x |
| Flux UI | 2.x Free, 2.x Pro |
| Inertia | 1.x, 2.x |
| Livewire | 1.x, 2.x, 3.x |
| Nova | 4.x, 5.x |
| Pest | 3.x, 4.x |
| Tailwind CSS | 3.x, 4.x |


## Adding Custom AI Guidelines

To augment Laravel Boost with your own custom AI guidelines, add `.blade.php` files to your application's `.ai/guidelines/*` directory. These files will automatically be included with Laravel Boost's guidelines when you run `boost:install`.

## Manually Registering the Boost MCP Server

Sometimes you may need to manually register the Laravel Boost MCP server with your editor of choice. You should register the MCP server using the following details:

<table>
<tr><td><strong>Command</strong></td><td><code>php</code></td></tr>
<tr><td><strong>Args</strong></td><td><code>artisan boost:mcp</code></td></tr>
</table>

JSON Example:

```json
{
    "mcpServers": {
        "laravel-boost": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

## Contributing

Thank you for considering contributing to Boost! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/boost/security/policy) on how to report security vulnerabilities.

## License

Laravel Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
