@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Migration Best Practices

## Generate Migrations with Artisan

Use `{{ $assist->artisanCommand('make:migration') }}` (e.g. `{{ $assist->artisanCommand('make:migration add_slug_to_posts_table') }}`) for the timestamped filename and structure.

## Define Foreign Keys Deliberately

Use `foreignId('user_id')->constrained()` when naming conventions and default actions fit; pass the table (`constrained('users')`) or add `cascadeOnDelete()` when they do not. Check whether the driver already indexes foreign keys before adding a duplicate index.

## Treat Deployed Migrations as Immutable

Once a migration has run in a shared or production environment, add a new migration instead of editing it; otherwise fresh installs differ from upgraded ones. Unshared local migrations can be edited and rerun.

## Design Indexes for Real Queries

Index for query patterns, selectivity, and write cost, not every column in `WHERE`, `ORDER BY`, or `JOIN`. Declare indexes in the migration that creates or changes the table, verify with the query plan, and avoid redundant indexes sharing leading columns without serving a distinct query. See the database performance and advanced query rules.

## Stage Changes That Affect Existing Rows

Adding a required or unique column such as `$table->string('slug')->unique()` to a populated table is unsafe in one step. Add it nullable, deploy code handling both states, backfill in bounded chunks, then add the constraint or index.

Prefer an observable, restartable command or job for large backfills. Small deterministic data changes in a migration are fine when locking, transaction, and deployment behavior are understood.

## Mirror Defaults Only When Unsaved Models Need Them

A database default applies on insert, not on instantiation. Mirror it in the model's `$attributes` only when code must see the value before saving, and keep both in sync.

## Make Rollbacks Honest

Implement `down()` when the change is safely reversible. Dropping populated columns or untransformable data is destructive even if reversible in code; document it and prefer forward-fix migrations in production.

## Keep Migrations Focused

Keep each migration small enough to reason about, deploy, and reverse. Separate long-running backfills from schema changes when that reduces locking, but do not split related operations just to separate schema from data.
