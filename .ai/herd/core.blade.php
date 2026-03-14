# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.
- Use the `herd` CLI to manage local services, PHP versions, and sites:
  - `herd services:available` — list available services (MySQL, Redis, Typesense, etc.)
  - `herd services:start <service>` / `herd services:stop <service>` — start or stop a service
  - `herd php:list` — list all PHP versions and their installation status
  - `herd php:install <version>` — install or update a specific PHP version
  - `herd sites` — list all sites with URLs, paths, security status, and PHP versions (supports `--json`)
  - `herd secure <site>` / `herd unsecure <site>` — toggle HTTPS for a site
  - `herd isolate <site> <php-version>` / `herd unisolate <site>` — assign a site to a specific PHP version or revert to global
