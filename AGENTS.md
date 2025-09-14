# Repository Guidelines

## Project Structure & Module Organization
- `src/`: Package source (e.g., `Console/`, `Mcp/`, `Install/`, service provider `BoostServiceProvider.php`).
- `tests/`: Pest tests using Testbench (`Feature/`, `Unit/`, `ArchTest.php`, `fixtures/`).
- `config/`: Package configuration stubs (if any).
- `.ai/`: AI guidelines bundled with the package (Blade files grouped by ecosystem).
- `art/`: Repository assets (logos, etc.).

## Build, Test, and Development Commands
- `composer install`: Install dependencies.
- `composer test`: Run the test suite via Pest.
- `composer lint`: Run Pint (format) and PHPStan (static analysis).
- `composer check`: Convenience script to run lint + tests.
- Direct binaries: `vendor/bin/pest`, `vendor/bin/phpstan`, `vendor/bin/pint`.

## Coding Style & Naming Conventions
- Indentation: 4 spaces; LF line endings (`.editorconfig`).
- Style: PSR-12-aligned via Pint (`pint.json` rules). Run `vendor/bin/pint` before pushing.
- Static analysis: PHPStan level 5 over `src/` and `config/`.
- Namespaces: `Laravel\\Boost\\...`. Class filenames match class names (StudlyCase); methods/properties in camelCase.
- Blade guideline files in `.ai/` use descriptive, versioned paths (e.g., `.ai/inertia-react/2/forms.blade.php`).

## Testing Guidelines
- Framework: Pest + PHPUnit Testbench. Place tests in `tests/Feature` or `tests/Unit`; file names end with `Test.php`.
- Run tests: `composer test`.
- Add tests for new features and bug fixes; keep assertions focused. Use provided helpers in `tests/Pest.php` (e.g., `expect($tool)->isToolResult()`).
- Architecture checks live in `tests/ArchTest.php`—update if structure changes.

## Commit & Pull Request Guidelines
- Commits: Clear, imperative subject lines; keep scope focused. Include context in body when needed.
- Branching: Target supported branches per Laravel’s support policy.
- PRs: Provide a descriptive title and thorough description; link related issues; include tests for changes. Follow `.github/PULL_REQUEST_TEMPLATE.md`.
- CI: PRs must pass linting, static analysis, and tests (see GitHub Actions workflows).

## Security & Configuration Tips
- Do not commit secrets. Tests run with safe defaults from `phpunit.xml.dist`.
- Report vulnerabilities via the Security Policy (`.github/SECURITY.md`).
