@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context
This application is a Laravel application running on PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

@if (! empty(config('boost.purpose')))
Application purpose: {!! config('boost.purpose') !!}

@endif

@if($assist->hasSkillsEnabled())
## Skills Activation
This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.
@endif

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `{{ $assist->nodePackageManagerCommand('run build') }}`, `{{ $assist->nodePackageManagerCommand('run dev') }}`, or `{{ $assist->composerCommand('run dev') }}`. Ask them.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.
