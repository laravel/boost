---
name: infer-conventions
description: "Use this skill to analyze how a Laravel application is actually written and record its conventions as shared rules. Trigger when the user wants to detect, infer, document, or standardize project conventions or coding style, set up or grow `.ai/rules`, resolve mixed or conflicting patterns (e.g. \"are we using Form Requests or inline validation?\"), or onboard agents and teammates to \"how we do things here\". Covers: a systematic sweep of ~49 Laravel convention dimensions (validation, models, architecture, testing, frontend, database, console), open-ended house-pattern discovery, conflict reporting, and recording rules scoped to the right paths via the Boost `record-rule` MCP tool. Do not use for one-off code review, enforcing formatting a linter already handles, or editing `.ai/rules` files by hand."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Infer Conventions

Learn how *this* application writes Laravel, then record what you learn as durable, path-scoped rules other agents will read. You are documenting reality, not improving it.

## Ground Rules (read before you start)

- Consistency first. The codebase's majority style *is* the convention. Never judge it, never propose a "better" pattern, never record what the code *should* do. If the app validates inline everywhere, that is the rule — even if Form Requests would be "nicer".
- Skip what a tool produces; keep what a tool would fight. Pint (formatting, imports, strict types) and `rector-laravel` rewrite code toward one canonical form — `$casts`→`casts()`, `$fillable`→attributes, magic accessors→the `Attribute` class, pipe-string rules→arrays, `$signature`→`#[Signature]`, named migrations→anonymous, and dozens more. When the app already sits at a tool's target form, the tool owns it — record nothing (it's a default too). But when the app deliberately holds the form a tool would refactor *away* (e.g. legacy `getXxxAttribute()` accessors the `Attribute` class would replace), no tool can reproduce that choice and an agent defaults the other way — *that* against-the-grain hold is exactly what to record.
- Record decisions, not defaults. A consistent pattern earns a rule only when it reflects a *choice* — the app took one valid option where the framework or common practice offered others, or the pattern would surprise a competent agent. Framework defaults (anonymous migrations, `$signature` commands, `ShouldQueue` jobs, `casts()` on Laravel 11+, named routes) and anything an agent already writes unprompted are no-ops — the rule steers nothing. A real fork is not enough — weigh the *side* the app took. Rule objects in `app/Rules` (the `make:rule` default), `Mail::fake()`/`Bus::fake()` to isolate framework services, and other idiomatic-default sides are what any agent already picks, so recording them steers nothing even though a technical alternative exists. Also beware the false fork — "no Mockery" next to facade fakes isn't a choice against Mockery; they double different things. Record the side an agent would *not* reach for on its own: inline closures everywhere, legacy accessors, a bespoke query layer. The test for every candidate: *without this rule, would the next agent plausibly write it differently?* Only "yes" earns a rule.
- Architecture choices are the gold — record presence *and* deliberate absence. The structural pattern the app commits to is the highest-signal convention and the one no tool can decide: Action classes (and how they're invoked — `handle`/`execute`/`__invoke`), service objects, dedicated query objects exposing `builder()`, DTOs (spatie/laravel-data vs readonly classes), Form Request validation vs inline, an events/listeners spine vs direct calls, domain/module folders. Equally record a consistent *non*-pattern — "query Eloquent directly in controllers, no repository layer" — so the next agent matches the app's altitude instead of over-engineering.
- Never duplicate `.ai/rules`. Read `.ai/rules/index.md` and the area files before the sweep. A dimension already covered there is marked done and skipped.
- Evidence or silence. A convention needs ≥3 consistent examples and no meaningful rival to be a candidate — the bar every Step 1 verdict applies.
- The recorded rule states the convention, nothing else. One or two imperative lines: *this project does X — do X here.* Keep detection evidence out — no counts, ratios, current-usage, file lists, or example paths; that's proof for the confirm step, not part of the rule. One short syntax fragment at most; point to `search-docs` for API details.

## Process

Each step ends on a checkable completion criterion. Do not advance until it holds.

Fan out when you can. The sweep is embarrassingly parallel. If your environment can spawn subagents (a Task, dispatch, or equivalent tool), do Step 0 yourself, then hand each checklist group (A–J) — and the architecture map — to its own subagent that runs the greps, reads a few representative files, and returns structured verdicts (*dimension · verdict · evidence · proposed glob / title / note*). You aggregate, dedupe, then run Steps 3–5. It is far faster on a real app. No subagents available? Run the steps sequentially — identical bar, identical output.

### Step 0 — Orient

Read `composer.json` (installed packages tell you which checklist groups apply), the `pint.json`/PHPStan/Rector config, `.ai/rules/index.md` (if present), and — most important — map the `app/` tree. List every directory under `app/` (and any `Modules/`, `src/`, `packages/`, or domain root). Every folder beyond Laravel's default skeleton (`Http`, `Models`, `Providers`, `Console`, `Exceptions`) is a structural pattern the app committed to and a high-value rule waiting to be written: `Actions`, `Services`, `Data`/DTOs, `Queries`, `Repositories`, `ViewModels`, `Pipelines`, `Support`, `Enums`, `Contracts`, `Observers`, or `Domain`/module roots. Note each one — you will confirm *how* it's used in Step 2.

@if($assist->hasPackage('livewire/livewire') || $assist->hasPackage('inertiajs/inertia-laravel') || $assist->hasPackage('livewire/flux') || $assist->hasPackage('livewire/flux-pro'))
This app ships a frontend stack, so the frontend checklist group applies — sweep it.
@else
This app has no Livewire/Inertia/Flux packages installed. Treat the frontend group as likely API-only: confirm from `resources/views` before spending time there, and skip the Livewire/Inertia/Flux dimensions.
@endif

*Done when:* you have the applicable checklist groups, the dimensions already recorded in `.ai/rules`, and a list of every non-default `app/` directory mapped to the pattern it represents.

### Step 1 — Predefined sweep

Open `references/checklist.md` and work every applicable dimension using its search hints. Give each exactly one verdict:

- Pattern — clears the bar, rival under ~20% of sites, *and* reflects a real choice (passes the decisions-not-defaults test). A recording candidate. Cite 2–3 example files.
- Conflict — both styles present in meaningful numbers. Report the split with counts and example files. Never auto-recorded, even in yolo — a conflict has no winner, and recording either side fights half the codebase. The user picks; their pick is recorded (say in the note that this was a decision, not a detection).
- Default — consistent, but it's a framework or common-practice default the agent already writes unprompted. Skip; it's a no-op, not a convention.
- No signal — under the bar: feature unused, or too few examples. Skip silently (one summary line at most).
- Tooling-owned / Already-recorded — skip per the ground rules.

*Done when:* every applicable dimension carries exactly one of those verdicts.

### Step 2 — Open-ended pass

First, close out the architecture map from Step 0: for every non-default `app/` directory you listed, confirm *how* the pattern is used and make it a candidate — e.g. Action classes invoked via `handle` / `execute` / `__invoke`; Services constructor-injected; `Queries` objects exposing `builder(): Builder`; DTOs as readonly classes or spatie/laravel-data; module/domain folders as the unit of organization. Each mapped pattern is a rule (scoped to its own directory glob). Equally, record a consistent *deliberate absence* — "no repository layer, controllers query Eloquent directly" — so the next agent matches the app's altitude.

Then find what else makes this codebase *itself* — base/abstract classes most code extends, traits used everywhere, tenancy or authorization scoping woven through queries, naming schemes, custom helpers. Same evidence bar, cite files. Record every genuine structural pattern; cap the *other* house findings at ~5 so the pass stays high-signal.

*Done when:* every non-default `app/` directory from Step 0 has a verdict, and the pass has produced its cited house findings (or concluded there are none).

### Step 3 — Confirm

Present every candidate in one batch. Per item: dimension, verdict, evidence (counts + files), and the exact proposed `glob` / `title` / `note`. Conflicts are presented as questions, not candidates.

Default mode is confirm — record only what the user approves. Switch to yolo only when the invocation said so ("yolo", "don't ask", "just record them"): record all *pattern* candidates without asking. Conflicts still go to the user in yolo.

*Done when:* every candidate is approved, rejected, or (conflicts) decided.

### Step 4 — Record

One `record-rule` call per approved convention. Choose the most specific glob that covers the cited evidence from the mapping table below. The `note` is the bare convention — strip every trace of detection (see the ground rule). If `record-rule` is unavailable (rules disabled), report the full rule text so the user can enable `BOOST_RULES_ENABLED` or add it by hand.

Record this:

> Accessors and mutators — use the legacy magic-method style (`getXxxAttribute()` / `setXxxAttribute()`), not the `Attribute` class. Match it in models.

Not this:

> Accessors/mutators use the legacy magic-method style; the `Attribute`-class style is not used anywhere (13 legacy, 0 Attribute-class), e.g. `app/Models/Post.php`. Match the legacy style in existing models.

*Done when:* every approved item has a successful tool response, and any failure is reported with its rule text.

### Step 5 — Summarize

List recorded rules (file + title), conflicts the user deferred, notable no-signals, and remind the user to commit `.ai/rules` so their team and agents share the conventions.

## Glob mapping — attach each rule to its namespace

Pick the most specific path that covers the evidence. Never a lazy `app/**` when a subtree fits.

| Convention domain | Glob |
|---|---|
| Models / Eloquent | `app/Models/**` |
| Validation, controllers, routing, responses | `app/Http/**` |
| Form Requests specifically | `app/Http/Requests/**` |
| API Resources | `app/Http/Resources/**` |
| Policies / authorization | `app/Policies/**` (plus `app/Http/**` when enforced in controllers) |
| Actions / Services / DTOs | `app/Actions/**` / `app/Services/**` / `app/Data/**` — whatever the app uses |
| Enums | `app/Enums/**` (or the observed location) |
| Jobs / events / listeners | `app/Jobs/**`, `app/Events/**`, `app/Listeners/**` |
| Console commands | `app/Console/Commands/**` |
| Views / components | `resources/views/**`, `resources/js/**` |
@if($assist->hasPackage('livewire/livewire'))
| Livewire | `app/Livewire/**` |
@endif
| Tests | `tests/**` |
| Migrations / database | `database/migrations/**`, `database/**` |
| Truly app-wide (rare — e.g. auth retrieval) | `app/**` |

`record-rule` takes one glob. When a convention genuinely spans two domains (e.g. UUID keys touch models *and* migrations), record it once under the primary domain and mention the secondary path in the note.

## Edge cases

- Rules disabled / `record-rule` missing: detection is read-only, so Steps 0–3 still run — recording falls back to the manual path in Step 4.
- Tiny or fresh app: most dimensions land on no-signal. Say so honestly — "not enough code to infer conventions yet" — and record nothing.
- Huge app: each dimension is a bounded grep plus a handful of file reads. Sample representative files; do not read everything.
- Re-runs: reading `.ai/rules` in Step 0 makes re-runs incremental — only new or undecided dimensions surface.
- Non-standard layout (modules, DDD): the open-ended pass catches the layout itself as convention #1; adapt the globs in the mapping table to the observed paths.
