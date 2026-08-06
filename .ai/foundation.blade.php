@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Boost & General Development Guidelines

## Foundational Context

- You're expert on Laravel running on PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}).
- Verify exact dependency versions before relying on APIs (`composer show --direct`, `composer show <vendor/package>`, or `package.json`). Do not assume versions, API signatures or alter dependencies without approval.
@if (! empty(config('boost.purpose')))
- Application purpose: {!! config('boost.purpose') !!}
@endif
@if($assist->hasSkillsEnabled())
- Skill files are located in `**/skills/**`. Read and follow the relevant skill file before starting work in that domain.
@endif

## 1. Think & Plan First
- **Expose Assumptions:** State core assumptions explicitly. If high-impact ambiguities exist (e.g., schema changes, destructive operations, or major architectural choices), propose multiple alternatives and seek approval before executing.
- **Surface Tradeoffs:** Identify overcomplicated requests and suggest simpler alternatives upfront.
- **Concise Communication:** Keep explanations focused strictly on key architectural decisions; omit self-evident implementation details.

## 2. Simplicity & Minimal Code
- **Scope Control:** Write the minimum code required for the request. Avoid speculative features, unrequested configurability, or single-use abstractions.
- **Documentation:** Do not create/update documentation files unless explicitly requested.
- **Senior Engineer Standard:** Keep implementation simple, idiomatic, and readable. If a senior developer would flag a pattern as over-engineered, simplify it.

## 3. Surgical Changes & Conventions
- **Convention Alignment:** Follow existing codebase standards, Laravel idioms (e.g., Eloquent, Form Requests, Collections), and local naming patterns (`isRegisteredForDiscounts` over `discount()`). Inspect sibling files for style and context.
- **Reuse First:** Prioritize existing components, helpers, and traits, over creating new ones. Laravel comes with many helpers to avoid verbose code (e.g. array/string manipulation, deeply nested values, iterations, serial tasks)
- **Surgical Edits:** Modify only necessary lines. Preserve existing whitespace and formatting, and do not refactor adjacent, unbroken code.
- **Orphan Cleanup:** Remove unused imports, variables, or functions introduced by *your* changes. Leave pre-existing dead code untouched unless requested.
- **Directory Integrity:** Preserve the established directory layout; do not create top-level directories without approval.

## 4. Goal-Driven Execution & Verification
- **Verifiable Success:** Define clear criteria for success (e.g., moving a failing test to passing) and iterate independently until verified.
- **Automated Testing:** Prioritize unit and feature tests (Pest/PHPUnit) over throwaway script execution or manual Tinker calls.
- **Frontend Asset Refresh:** If UI updates do not render, inform the user to run:
  `{{ $assist->nodePackageManagerCommand('run build') }}`, `{{ $assist->nodePackageManagerCommand('run dev') }}`, or `{{ $assist->composerCommand('run dev') }}`.
