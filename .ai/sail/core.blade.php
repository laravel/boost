## Laravel Sail

- This project runs inside Laravel Sail's Docker containers.** You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands** with `vendor/bin/sail`. Examples:
  - Run migrations: `vendor/bin/sail artisan migrate`
  - Install Composer packages: `vendor/bin/sail composer install`
  - Run npm: `vendor/bin/sail npm run dev`
  - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.
