---
name: deploying-to-cloud
description: "Deploys and manages Laravel applications on Laravel Cloud using the `cloud` CLI. Use when the user wants to deploy an app, ship to cloud, create/manage applications, environments, databases, caches, object storage, queues, domains, instances, background processes, secrets, compute, scheduled tasks, check billing/usage/spend, or any Laravel Cloud infrastructure. Triggers on deploy, ship, cloud management, environment setup, database provisioning, billing/usage queries, the `cloud` CLI, and troubleshooting Laravel Cloud deployments."
license: MIT
metadata:
  author: laravel
---
# Deploying with Laravel Cloud

Use the [Laravel Cloud documentation](https://cloud.laravel.com/docs/llms.txt) for detailed and current feature behavior. Use the Cloud CLI for Cloud operations rather than guessing dashboard or API workflows. Install it in the project and invoke it as `./vendor/bin/cloud` by default; use a globally installed `cloud` command only as a fallback. Commands below are written as `cloud` for brevity — run them as `./vendor/bin/cloud` unless falling back to the global installation.

## Application Setup

- Laravel Cloud deploys from GitHub, GitLab, or Bitbucket. A new Laravel application requires PHP 8.2 or greater, Laravel 9 or greater, a connected Git provider, and a deployment region.
- Cloud creates environments for applications. Use separate production, staging, and preview environments; each environment has its own compute, resources, and deployment settings.
- Keep application compute and attached resources in the same region where possible.
- Cloud builds a Docker image using the selected PHP version, runs the configured build and deploy commands, and switches traffic to a successful deployment with zero downtime. Push-to-deploy is enabled by default, and manual deployments and deploy hooks are also available.

## Build And Deploy

- A typical Laravel build command is `composer install --no-dev && npm run build`.
- Run optimization and cache-building commands during the build, not the deploy. A typical deploy command is `php artisan migrate --force`.
- Build and deploy commands have a 15-minute timeout. Deploy commands run immediately before the release becomes live, and filesystem changes made by deploy commands are not persisted.
- Do not add `php artisan queue:restart`, `php artisan horizon:terminate`, `php artisan optimize:clear`, or `php artisan storage:link` to deploy commands. Cloud handles worker restarts and process management; deploy filesystem changes are not persistent.
- After changing environment settings, attached resources, or linked secrets, redeploy the environment for the changes to take effect.

## Configuration And Resources

- Attached databases, caches, and object storage inject their connection variables automatically. Custom environment variables take precedence over injected values.
- Use Secrets Manager for encrypted organization-level values shared across environments. Secret values cannot be read after creation; redeploy affected environments after creating, updating, linking, unlinking, or deleting a secret.
- Environment filesystems are ephemeral and are not shared between replicas. Use a database or Laravel Valkey for persistent cache and sessions, and Laravel Cloud Object Storage for persistent files. Do not rely on local files surviving a deployment.

### Object Storage And File Visibility

- Laravel Cloud Object Storage is backed by Cloudflare R2. R2 applies visibility at the bucket level; a bucket cannot contain a mix of private and public objects.
- For private files, always use `Storage::disk()` with no arguments, such as `Storage::disk()->put(...)`, so Laravel uses the environment's default disk. Do not pass a disk name for private/default storage. Attach a private Cloud bucket as the environment's default disk.
- For public files, use the named public disk: `Storage::disk('public')->put(...)`. Attach a second Cloud bucket configured as public and give it the `public` disk name.
- Applications that use both private and public files therefore need two Cloud Object Storage buckets attached to the environment: a private default bucket and a public bucket named `public`.
- Do not set per-file or Flysystem `visibility: 'public'` configuration for Cloud Object Storage. R2 does not support per-object ACL headers and rejects those requests; select the bucket visibility when creating the bucket.
- Private buckets are not internet-accessible, but Laravel can generate temporary public URLs with `Storage::temporaryUrl(...)`. Public buckets expose all objects through their Cloud-provided public URL.
- Install the S3 Flysystem adapter before using Cloud Object Storage: `composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies`.

## Queues And Scheduling

- Managed queues are the recommended queue option. They provision dedicated workers and autoscale based on queued work.
- Managed queues require a supported recent Laravel version and `aws/aws-sdk-php`; check the current Cloud documentation before changing dependencies.
- Enable the scheduler on an App or Worker cluster to run `php artisan schedule:run` every minute. When an environment has multiple replicas, use Laravel's `onOneServer` for tasks that must run once.
- Only Flex compute sizes can scale to zero. Scale-to-zero environments wake for Laravel scheduled tasks and queued jobs, but long-running jobs may be interrupted when the sleep timeout is reached. Use managed queues for workloads that must not be interrupted.

## Domains And Storage

- Each environment receives a `laravel.cloud` domain after its first successful deployment. Cloud automatically verifies custom domains and provisions SSL after the required DNS records are configured.
- Do not use the ephemeral filesystem for persistent uploads, generated files, cache, or session data. Use the appropriate Cloud resource instead.

## Cloud CLI

### Setup

```shell
composer require --dev laravel/cloud-cli
./vendor/bin/cloud auth -n
```

If a project-local installation is not available, install the CLI globally with `composer global require laravel/cloud-cli` and invoke it as `cloud`.

`cloud auth` opens a browser. Where that isn't possible, set `LARAVEL_CLOUD_TOKEN` in the environment — it overrides any saved token and writes nothing to disk. To save a token instead: `cloud auth:token --add --token=<token> -n`, or pipe it: `echo "$TOKEN" | cloud auth:token --add -n`.

### Commands

Commands follow a CRUD pattern: `resource:list`, `resource:get`, `resource:create`, `resource:update`, `resource:delete`.

Available resources: `application`, `environment`, `instance`, `database-cluster`, `database`, `cache`, `bucket`, `domain`, `websocket-cluster`, `background-process`, `secret`, `command`, `deployment`.

Some resources have additional commands (e.g., `domain:verify`, `database:open`, `instance:sizes`, `cache:types`). Discover these via `cloud -h`.

Never hardcode command signatures. Always run `cloud <command> -h` to discover options at runtime.

### CLI Flags

Always add `-n` to every command — prevents the CLI from hanging.
Never use `-q` or `--silent` — they suppress all output.

Flag combos per operation:
- Read (`:list`, `:get`) → `--json -n`
- Create (`:create`) → `--json -n`
- Update (`:update`) → `--json -n --force`
- Delete (`:delete`) → `-n --force` (no `--json`)
- Environment variables → `-n --force`
- Deploy/ship → `-n` with all options passed explicitly (no `--json`)

### Deployment Workflow

Determine the task and follow the matching path:

First deploy? → inspect `cloud ship -h`, then run `cloud ship -n` with all required values

Existing app? →
```shell
cloud deploy {app_name} {environment} -n --open
cloud deploy:monitor -n
```

Environment variables? → `cloud environment:variables -n --force`

Secrets? → `echo "$VALUE" | cloud secret:create --name=NAME --json -n` then `cloud environment-secret:attach`

Provision infrastructure? → `cloud <resource>:create --json -n`

Monorepo (app in a subdirectory)? → add `--root-directory=<subdir>` to `cloud ship` or `cloud application:create`

Custom domain? → `cloud domain:create --json -n` then `cloud domain:verify -n`

Repository defaults? → `cloud repo:config {application} -n` sets repository-local application and organization defaults. Pass `--organization=<id|name|slug>` when the user has multiple organizations.

For multi-step operations, see [reference/checklists.md](reference/checklists.md).

Not sure what the user needs? → ask them before running anything.

### When a Command Fails

1. Read the error output
2. Check resource status with `:list --json -n` or `:get --json -n`
3. Auth error? → `cloud auth -n`
4. Fix the issue, re-run the command
5. If the same error repeats after one fix, stop and ask the user rather than repeating the same operation

Always run `cloud deploy:monitor -n` after every deploy. If it fails, inspect the deployment status and logs and show the user what went wrong before attempting a fix.

### Subagent Delegation

Delegate high-output operations to subagents (using the Task tool) to keep the main context window small. Only the summary comes back — verbose output stays in the subagent's context.

Delegate these to a subagent:
- `cloud deploy:monitor -n` — deployment logs can be very long
- `cloud deployment:get --json -n` — full deployment details
- `cloud <resource>:list --json -n` — listing many resources produces large JSON
- `cloud command:run` — when output may be long
- `cloud usage --detailed --json -n` — the payload includes every database, cache, bucket, websocket, and application
- Fetching docs from https://cloud.laravel.com/docs/llms.txt via `WebFetch`

Keep in the main context:
- Short commands like `:create`, `:delete`, `:update` — output is small
- `cloud deploy -n` — you need the deployment ID immediately
- Any command where you need the result for the next step right away

### Rules

Follow exact steps:
- Flag selection — always use the documented combos above
- Deploy sequence — deploy then monitor, never skip monitoring
- Destructive commands — always confirm with the user first, show the command and wait for approval. This includes deleting applications, environments, databases, caches, buckets, domains, or secrets.
- Error loop — diagnose, fix once, ask user if it fails again

Use your judgment:
- Instance sizes, regions, cluster types — ask the user if not specified
- Which resources to provision — based on what the user describes
- Order of provisioning — no strict sequence required
- How to present output — summarize, show raw, or extract fields based on context

### Secrets

Encrypted values shared across the organization and attached to environments. The CLI encrypts the value locally, so plaintext never reaches the API.

```shell
cloud secret:list --json -n
echo "$VALUE" | cloud secret:create --name=STRIPE_KEY --json -n
cloud environment-secret:attach {environment} {secretId} -n
cloud environment-secret:list {environment} --json -n
```

Pipe the value in. `--value=` works, but leaves the plaintext in shell history and the process list.

`secret:update`, `secret:delete`, and `environment-secret:attach` take secret IDs, not names — names are not unique. Read IDs from `cloud secret:list --json -n`.

There is no `secret:get`, and no way to detach a secret from a single environment. `secret:delete` removes it and detaches it everywhere.

Secrets need the `sodium` PHP extension. No other command does.

Redeploy affected environments after creating, updating, attaching, or deleting a secret.

### Remote Access

#### Tinker (>= v0.2.0)

Run PHP code directly in a Cloud environment:

```shell
cloud tinker {environment} --code='Your PHP code here' --timeout=60 -n
```

- `--code` — PHP code to execute (required in non-interactive mode)
- `--timeout` — max seconds to wait for output (default: 60)

The code must explicitly output results using `echo`, `dump`, or similar — expressions alone produce no output.

Always pass `--code` and `-n` to avoid interactive prompts.

#### Remote Commands

Run shell commands on a Cloud environment:

```shell
cloud command:run {environment} --cmd='your command here' -n
```

- `--cmd` — the command to run (required in non-interactive mode)
- `--no-monitor` — skip real-time output streaming
- `--copy-output` — copy output to clipboard

Review past commands:

- `cloud command:list {environment} --json -n` — list command history
- `cloud command:get {commandId} --json -n` — get details and output of a specific command

### Billing & Usage

View billing and usage for the current organization:

```shell
cloud usage --json -n
```

- `--period=current|previous|1|2|3` — billing period (default `current`; `1`/`2`/`3` are N periods back, max 3). Anything else errors out.
- `--environment=<id>` — filter usage to a single environment
- `--detailed` — include per-application, per-resource, and per-add-on breakdowns
- `--json` — machine-readable output (always pair with `-n`)

Common queries:

- Current spend: `cloud usage --json -n | jq '.currentSpendCents'`
- Last month's bill: `cloud usage --period=previous --json -n`
- One environment, full breakdown: `cloud usage --environment=<id> --detailed --json -n`

All amounts are in cents. Keys are camelCase at every level (e.g. `currentSpendCents`, `bandwidth.allowanceBytes`, `databases[].totalCents`, `applications[].totalCostCents`).

### Config

1. Environment: `LARAVEL_CLOUD_TOKEN` — an API token, taking precedence over any saved one (empty counts as unset)
2. Global: `~/.config/cloud/config.json` — auth tokens and preferences
3. Repo-local: `.cloud/config.json` — app and environment defaults (set by `cloud repo:config {application} -n`)
4. CLI arguments override both config files

Pass the application to `repo:config` — without it the command has to ask, and under `-n` it fails when the organization has more than one application. Deploy commands don't need these defaults; pass the application and environment to them directly.

Multiple organizations means multiple stored API tokens. Every command reads `organization_id` from `.cloud/config.json` to pick one; if it isn't set, they fail. Set it with `cloud repo:config {application} --organization=<id|name|slug> -n`.

`LARAVEL_CLOUD_TOKEN` holds one token, so it picks the organization on its own. Naming a different one with `--organization` fails rather than falling back to the token's organization.

## Documentation

Laravel Cloud Docs: https://cloud.laravel.com/docs/llms.txt

When the user asks how something works or needs an explanation of a Laravel Cloud feature, fetch the docs from the URL above using `WebFetch` and use it to provide accurate answers.

## When Stuck

- Fetch https://cloud.laravel.com/docs/llms.txt for official documentation
- Run `cloud <command> -h` for any command's options
- Run `cloud -h` to discover commands

## Verification

1. Confirm the target application and environment before changing or deploying anything.
2. Check staged environment changes and required resources before deployment.
3. Monitor every deployment to completion with `cloud deploy:monitor -n`.
4. For production changes, verify the application URL, logs, queues, scheduled tasks, and relevant resource health.
