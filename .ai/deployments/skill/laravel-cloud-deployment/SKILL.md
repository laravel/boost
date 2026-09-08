---
name: laravel-cloud-deployment
description: "Use when deploying or managing a Laravel application on Laravel Cloud. Trigger for Cloud deployments, applications, environments, databases, caches, object storage, queues, domains, secrets, compute, scheduled tasks, billing or usage, the `cloud` CLI, and troubleshooting Laravel Cloud deployments."
license: MIT
metadata:
  author: laravel
---
# Laravel Cloud Deployment

Use the [Laravel Cloud documentation](https://cloud.laravel.com/docs/llms.txt) for detailed and current feature behavior. Use the Cloud CLI for Cloud operations rather than guessing dashboard or API workflows. Install it in the project and invoke it as `./vendor/bin/cloud` by default; use a globally installed `cloud` command only as a fallback.

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

Where browser authentication is unavailable, set `LARAVEL_CLOUD_TOKEN`. It overrides any saved token and writes nothing to disk.

### Rules

- Discover command signatures at runtime with `./vendor/bin/cloud <command> -h`; never assume or hardcode CLI options. Use `cloud <command> -h` only when falling back to the global installation.
- Use `./vendor/bin/cloud` for all commands by default. Fall back to `cloud` only when the project-local installation is unavailable.
- Always pass `-n` to Cloud CLI commands. Use `--json -n` for read and create operations, `--json -n --force` for updates, and `-n --force` for deletes. Never use `-q` or `--silent`.
- Available CRUD resources include applications, environments, instances, databases, caches, buckets, domains, background processes, secrets, commands, and deployments. Use `cloud -h` to discover additional commands.
- For a first deployment, inspect `cloud ship -h`, then run `cloud ship -n` with all required values.
- For an existing application, deploy with `cloud deploy {application} {environment} -n --open`, then run `cloud deploy:monitor -n`.
- After every deployment, run `cloud deploy:monitor -n` and report failures before attempting a fix.
- Use `cloud environment:variables -n --force` for environment variables and `cloud command:run {environment} --cmd='...' -n` for non-interactive remote commands.
- Use `--root-directory=<subdirectory>` when deploying an application from a monorepo subdirectory.
- For secrets, pipe values to the CLI rather than putting plaintext in command arguments or shell history. Secret update, delete, and environment attachment operations use secret IDs, not names.
- Use `cloud repo:config {application} -n` to set repository-local application and organization defaults. Pass `--organization=<id|name|slug>` when the user has multiple organizations.

### Remote Access And Usage

- Run non-interactive PHP with `cloud tinker {environment} --code='...' --timeout=60 -n`; the code must explicitly output values with `echo`, `dump`, or similar.
- Run shell commands with `cloud command:run {environment} --cmd='...' -n`. Use `cloud command:list {environment} --json -n` and `cloud command:get {commandId} --json -n` to inspect command history.
- Use `cloud usage --json -n` for current usage. Add `--period=previous` for the prior billing period, `--environment=<id>` to filter, and `--detailed` for per-resource breakdowns. Monetary values are returned in cents.

### Safety

- Confirm with the user before destructive Cloud operations, including deleting applications, environments, databases, caches, buckets, domains, or secrets.
- If a deployment fails, inspect the deployment status and logs, make one targeted correction, and retry. If it fails again, stop and ask the user rather than repeating the same operation.

## Verification

1. Confirm the target application and environment before changing or deploying anything.
2. Check staged environment changes and required resources before deployment.
3. Monitor every deployment to completion with `cloud deploy:monitor -n`.
4. For production changes, verify the application URL, logs, queues, scheduled tasks, and relevant resource health.
