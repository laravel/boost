# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Laravel Boost** is a developer tool that accelerates AI-assisted development by providing essential context and structure. It operates as an MCP (Model Context Protocol) server with over 15 specialized tools, composable AI guidelines for Laravel ecosystem packages, and a powerful Documentation API with semantic search.

The project bridges the gap between AI coding assistants (Claude, Cursor, etc.) and Laravel applications by offering:
- MCP tools for database access, Artisan commands, log reading, Tinker execution
- AI guidelines (as Blade/Markdown) for consistent code generation across the Laravel ecosystem
- Dynamic discovery of third-party package guidelines without global context bloat
- Support for multiple code editors (PhpStorm, VS Code, Cursor, Claude Code, etc.)

## Quick Start: Common Commands

### Development
```bash
composer install              # Install dependencies
composer test                 # Run Pest test suite (full)
composer test tests/Unit      # Run only unit tests
composer test tests/Feature   # Run only feature tests
composer lint                 # Format (Pint), type-check (PHPStan), auto-refactor (Rector)
composer test:lint            # Check only (no changes): style + refactor rules
composer test:types           # PHPStan static analysis (level 5)
composer check                # Run both lint + test (CI equivalent)
```

### Running a Single Test
Tests use Pest's intuitive syntax. Run individual tests with:
```bash
composer test tests/Unit/BoostManagerTest.php
composer test --filter "returns default code environments"  # By test name
```

## Architecture Overview

### High-Level Structure

**Laravel Boost** has four main responsibility areas:

1. **MCP Server** (`src/Mcp/`) — Implements the Model Context Protocol
   - `Boost.php` — Server entrypoint that discovers and registers tools, resources, and prompts
   - `Tools/` — 15+ tools providing database schema, logs, Artisan commands, etc.
   - `Resources/` — MCP resources (searchable guidelines) including `ThirdPartyResource`
   - `Prompts/` — MCP prompts (context on request) including `ThirdPartyPrompt`

2. **Installation & Setup** (`src/Install/`) — Interactive configuration and environment setup
   - `GuidelineComposer.php` — Aggregates guidelines from Boost, Laravel packages, and third-party packages
   - `GuidelineWriter.php` — Writes guidelines to `.ai/guidelines/` for the user's project
   - `CodeEnvironment/` — Abstractions for various IDEs (Cursor, VS Code, PhpStorm, Claude Code, etc.)
   - `Console/` — Artisan commands: `boost:install`, `boost:update`, `boost:mcp`

3. **Service Integration** (`src/Services/`, `src/Middleware/`) — Application-level integrations
   - `InjectBoost.php` — Middleware to make Boost context available in routes
   - Service classes for documentation, feedback, and other cross-cutting concerns

4. **Support & Utilities** (`src/Support/`, `src/Contracts/`) — Helpers and interfaces
   - `Composer.php` — Reads `composer.json` to discover packages and their Boost guidelines
   - `Contracts/` — Interfaces for agents and MCP clients

### Key Design Patterns

**Dynamic Guideline Discovery**
- Boost automatically discovers guidelines in three places:
  1. Built-in Boost guidelines (`resources/boost/guidelines/`)
  2. User custom guidelines (`.ai/guidelines/`)
  3. **Third-party package guidelines** (`vendor/*/resources/boost/guidelines/`) — NEW
- `GuidelineComposer` merges them with intelligent priority handling (user > third-party > Boost defaults)

**Third-Party Resource & Prompt Abstraction** (Current MR focus)
- `ThirdPartyResource` — Makes third-party package guidelines discoverable as MCP resources
- `ThirdPartyPrompt` — Makes them available as context when the LLM requests them
- Benefit: Keeps global context small; guidelines load on-demand per package

**Code Environment Abstraction**
- `CodeEnvironment` is the interface for different editors
- Each environment can implement `Agent` (guidelines support) and/or `McpClient` (MCP support)
- `BoostManager` maintains a registry of available environments, allowing custom registration

**Blade Template Rendering**
- Guidelines are `.blade.php` or `.md` files rendered by Laravel's Blade engine
- Traits like `RendersBladeGuidelines` handle the rendering pipeline
- Allows dynamic, version-aware content (e.g., "Laravel 11 way" vs. "Laravel 10 way")

## Testing Guidelines

**Framework & Organization**
- **Pest** with PHPUnit under the hood, using Orchestra Testbench
- Unit tests in `tests/Unit/` (logic isolation)
- Feature tests in `tests/Feature/` (integration with Laravel)
- Architecture rules in `tests/ArchTest.php` (namespace, class constraints)

**Writing Tests**
- Use descriptive test names in present tense: `it('generates config safely')`
- One behavior per test; use `expect()` fluently
- Fixtures in `tests/Fixtures/` for mock data
- Tests default to SQLite; avoid external service calls in unit tests

**Example Test Pattern** (from `tests/Unit/BoostManagerTest.php`)
```php
it('returns default code environments', function (): void {
    $manager = new BoostManager;
    $registered = $manager->getCodeEnvironments();
    expect($registered)->toMatchArray([...]);
});
```

## Code Organization & Key Modules

### `src/Mcp/` — Model Context Protocol Server
- **Boost.php**: Extends Laravel's `Server` class; discovers tools, resources, and prompts at boot time
- **Tools/**: Database schema, query execution, config reading, log access, Tinker integration, documentation search
  - Tools extend `Tool` class and implement `execute()` with schema validation
  - DatabaseSchema/ handles complex schema introspection per connection
- **Resources/**: MCP resources (user can browse/search them)
  - `ApplicationInfo` — Lists PHP version, packages, Eloquent models
  - `ThirdPartyResource` — Dynamically generated from third-party package guidelines
- **Prompts/**: MCP prompts (LLM can request them)
  - `ThirdPartyPrompt` — Dynamically generated from third-party package guidelines
- **Methods/**: Custom MCP method implementations (e.g., `CallToolWithExecutor`)

### `src/Install/` — Setup & Configuration
- **GuidelineComposer.php**: Aggregates guidelines, handles package priority, respects include/exclude rules
- **GuidelineWriter.php**: Writes `.ai/guidelines/` files and generates `CLAUDE.md`, `AGENTS.md`, `.mcp.json`
- **GuidelineConfig.php**: Loads version metadata for installed packages
- **GuidelineAssist.php**: Bridges Composer and Writer; manages the overall pipeline
- **Herd.php**, **Sail.php**: Platform detection for Laravel development environments
- **CodeEnvironmentsDetector.php**: Auto-detects available IDEs
- **CodeEnvironment/**: IDE abstractions (VSCode, Cursor, PhpStorm, Claude Code, etc.)
  - Implements file writing strategies specific to each editor's config format
- **Detection/**: Strategy pattern for detecting package installation (file, directory, command-based)

### `src/Support/` — Utilities
- **Composer.php**:
  - `packages()` — Reads `composer.json` requires (dev + main)
  - `packagesDirectories()` — Maps package names to vendor paths
  - `packagesDirectoriesWithBoostGuidelines()` — Filters to only packages with guidelines
- **Config.php**: Loads Boost configuration

### `src/Concerns/` & `src/Services/` — Cross-Cutting Features
- Shared traits and service classes for guidelines, feedback, documentation

## Code Style & Lint Rules

**PHP Formatting**
- PSR-12 via **Pint** (`preset: laravel`) with 4-space indents, LF EOL
- Configuration in `pint.json` (strict comparisons, no unused variables)

**Static Analysis**
- **PHPStan** at level 5 with `phpstan.neon.dist`
- Detects type issues, undefined variables, missing return types

**Auto-Refactoring**
- **Rector** with `rector.php`; safe refactors only (e.g., upgrade syntax)

**Architecture Rules**
- `tests/ArchTest.php` enforces namespace patterns and class constraints

## File Locations for Common Tasks

| Task                      | Location(s)                                                                               |
|---------------------------|-------------------------------------------------------------------------------------------|
| Add a new MCP tool        | `src/Mcp/Tools/NewToolName.php` + extend `Tool` + add to `discoverTools()` in `Boost.php` |
| Add a new Artisan command | `src/Console/NewCommand.php` + register in `BoostServiceProvider`                         |
| Add IDE/editor support    | `src/Install/CodeEnvironment/NewEditor.php` + register in `BoostManager.php`              |
| Add package detection     | `src/Install/Detection/NewDetectionStrategy.php` + use in factory                         |
| Add AI guidelines         | `.ai/guidelines/` (for user overrides) or built-in at `resources/boost/guidelines/`       |
| Test MCP functionality    | `tests/Feature/`                                                                          |
| Test utilities/logic      | `tests/Unit/`                                                                             |

## Important Implementation Details

### Third-Party Guideline Discovery (Current Focus)
- **Entry point**: `Composer::packagesDirectoriesWithBoostGuidelines()`
- **Format**: `vendor/{package}/resources/boost/guidelines/core.blade.php`
- **Integration**: `Boost.php` dynamically creates `ThirdPartyResource` and `ThirdPartyPrompt` instances
- **Benefit**: No need to modify global guidelines; packages self-register

### MCP Server Boot Sequence
1. `Boost::boot()` is called when Laravel app boots
2. `discoverTools()`, `discoverResources()`, `discoverPrompts()` are called
3. Resources/prompts are filtered to only "primitive" types (no instance-based logic in class names)
4. Tools use a custom `CallToolWithExecutor` method for execution

### Guideline Rendering Pipeline
1. `GuidelineComposer` collects `.blade.php` and `.md` files
2. `RendersBladeGuidelines` trait renders Blade files with version context
3. `GuidelineWriter` writes rendered output to user's `.ai/guidelines/`
4. Each IDE reads from its own config location (e.g., VS Code `.vscode/settings.json`)

## Security & Configuration Notes

- Secrets and API keys should not be committed (use `.env` and `config/`)
- IDE config files (`.vscode/`, `.idea/`) and generated files (`CLAUDE.md`, `.mcp.json`) are typically in `.gitignore`
- Tests use SQLite by default to avoid external dependencies
- The `Tinker` MCP tool executes arbitrary code; designed for local development only

## Commit & PR Guidelines

- Use clear, present-tense messages; follow Conventional Commits (`feat:`, `fix:`, `chore:`)
- Reference issues/PRs: `feat: add X support (#123)`
- Ensure `composer check` passes (lint + tests)
- Update `CHANGELOG.md` for user-facing changes
- Update tests and docs when behavior changes
- Keep PRs focused on a single concern

## Related Files & References

- **README.md** — Feature overview, installation, MCP tool list, guidelines list
- **AGENTS.md** — Repository guidelines (architecture, code style, testing) — currently in draft
- **CHANGELOG.md** — Version history and breaking changes
- **config/boost.php** — Published configuration for the package
- **composer.json** — Scripts for lint, test, check; dependencies and their versions
