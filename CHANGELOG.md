# Release Notes

## [Unreleased](https://github.com/laravel/boost/compare/v1.0.20...main)

## [v1.0.20](https://github.com/laravel/boost/compare/v1.0.19...v1.0.20) - 2025-08-28

### What's Changed

* fix: defer InjectBoost middleware registration until app is booted by [@Sairahcaz](https://github.com/Sairahcaz) in https://github.com/laravel/boost/pull/172
* feat: add robust MCP file configuration writer by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/204
* Feat: Detect env changes by default, fixes 130 by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/217

### New Contributors

* [@Sairahcaz](https://github.com/Sairahcaz) made their first contribution in https://github.com/laravel/boost/pull/172

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.19...v1.0.20

## [v1.0.19](https://github.com/laravel/boost/compare/v1.0.18...v1.0.19) - 2025-08-27

### What's Changed

* Refactor creating laravel application instance using Testbench by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/boost/pull/127
* Fix Tailwind CSS title on README.md for consistency by [@xavizera](https://github.com/xavizera) in https://github.com/laravel/boost/pull/159
* feat: don't run Boost during testing by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/144
* Hide Internal Command `ExecuteToolCommand.php` from Artisan List by [@yitzwillroth](https://github.com/yitzwillroth) in https://github.com/laravel/boost/pull/155
* chore: removes non necessary php version constrant by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/boost/pull/166
* chore: removes non necessary pint version constrant by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/boost/pull/167
* Do not autoload classes while boost:install by [@pushpak1300](https://github.com/pushpak1300) in https://github.com/laravel/boost/pull/180
* fix: prevent unwanted "null" file creation on Windows during installation by [@andreilungeanu](https://github.com/andreilungeanu) in https://github.com/laravel/boost/pull/189
* Improve `InjectBoost` middleware for response-type handling by [@pushpak1300](https://github.com/pushpak1300) in https://github.com/laravel/boost/pull/179
* docs: README: Add Nova 4.x and 5.x by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/213
* refactor: change ./artisan to artisan by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/214
* feat: guidelines: add Inertia form guidelines by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/211

### New Contributors

* [@crynobone](https://github.com/crynobone) made their first contribution in https://github.com/laravel/boost/pull/127
* [@xavizera](https://github.com/xavizera) made their first contribution in https://github.com/laravel/boost/pull/159
* [@nunomaduro](https://github.com/nunomaduro) made their first contribution in https://github.com/laravel/boost/pull/166
* [@andreilungeanu](https://github.com/andreilungeanu) made their first contribution in https://github.com/laravel/boost/pull/189

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.18...v1.0.19

## [v1.0.18](https://github.com/laravel/boost/compare/v1.0.17...v1.0.18) - 2025-08-16

### What's Changed

* fix: Prevent install command from breaking when `/tests` doesn't exist by [@sagalbot](https://github.com/sagalbot) in https://github.com/laravel/boost/pull/93
* [1.x] Add enabled option to `config/boost.php`. by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/boost/pull/143

### New Contributors

* [@sagalbot](https://github.com/sagalbot) made their first contribution in https://github.com/laravel/boost/pull/93
* [@xiCO2k](https://github.com/xiCO2k) made their first contribution in https://github.com/laravel/boost/pull/143

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.17...v1.0.18

## [v1.0.17](https://github.com/laravel/boost/compare/v1.0.16...v1.0.17) - 2025-08-14

### What's Changed

* Fix: Replace APP_DEBUG with environment-based gating by [@eduardocruz](https://github.com/eduardocruz) in https://github.com/laravel/boost/pull/90

### New Contributors

* [@eduardocruz](https://github.com/eduardocruz) made their first contribution in https://github.com/laravel/boost/pull/90

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.16...v1.0.17

## [v1.0.16](https://github.com/laravel/boost/compare/v1.0.15...v1.0.16) - 2025-08-14

### What's Changed

* refactor: streamline path resolution and simplify the MCP client interface by [@pushpak1300](https://github.com/pushpak1300) in https://github.com/laravel/boost/pull/111
* Fix PHPStorm using absolute paths by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/109

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.15...v1.0.16

## [v1.0.15](https://github.com/laravel/boost/compare/v1.0.14...v1.0.15) - 2025-08-14

### What's Changed

* fixes #67 by only finding files that begin with an uppercase letter by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/116

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.14...v1.0.15

## [v1.0.14](https://github.com/laravel/boost/compare/v1.0.13...v1.0.14) - 2025-08-14

### What's Changed

* Fixes #85 by adding verbatim to flux component example by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/114

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.13...v1.0.14

## [v1.0.13](https://github.com/laravel/boost/compare/v1.0.12...v1.0.13) - 2025-08-14

### What's Changed

* Fix volt blade parsing by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/112

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.12...v1.0.13

## [v1.0.12](https://github.com/laravel/boost/compare/v1.0.11...v1.0.12) - 2025-08-14

### What's Changed

* tool: tinker: try to nudge away from creating test users ahead of time by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/108

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.11...v1.0.12

## [v1.0.11](https://github.com/laravel/boost/compare/v1.0.10...v1.0.11) - 2025-08-14

### What's Changed

* tools: report-feedback: strengthen language on privacy by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/103

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.10...v1.0.11

## [v1.0.10](https://github.com/laravel/boost/compare/v1.0.9...v1.0.10) - 2025-08-14

### What's Changed

* fixes #70 - make sure foundational rules are composed by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/84
* Update the bug report template's system info section by [@pushpak1300](https://github.com/pushpak1300) in https://github.com/laravel/boost/pull/98
* Update Filament Guidelines by [@pushpak1300](https://github.com/pushpak1300) in https://github.com/laravel/boost/pull/35
* Fix: Prevent autoloading non class-like files during discovery to avoid "FatalError: Cannot redeclare function" by [@zdearo](https://github.com/zdearo) in https://github.com/laravel/boost/pull/99

### New Contributors

* [@zdearo](https://github.com/zdearo) made their first contribution in https://github.com/laravel/boost/pull/99

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.9...v1.0.10

## [v1.0.9](https://github.com/laravel/boost/compare/v1.0.8...v1.0.9) - 2025-08-13

### What's Changed

* fixes #80 - install Boost MCP into Claude via file instead of shell by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/82

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.8...v1.0.9

## [v1.0.8](https://github.com/laravel/boost/compare/v1.0.3...v1.0.8) - 2025-08-13

### What's Changed

* fixes #80 by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/81

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.7...v1.0.8

## [v1.0.3](https://github.com/laravel/boost/compare/v1.0.2...v1.0.3) - 2025-08-13

### What's Changed

* Update Pint Guideline to Use `--dirty` Flag by [@yitzwillroth](https://github.com/yitzwillroth) in https://github.com/laravel/boost/pull/43
* docs: README: add filament by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/58
* Fix Herd detection by [@mpociot](https://github.com/mpociot) in https://github.com/laravel/boost/pull/61
* fix #49: disable boost inject if HTML isn't expected by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/60

### New Contributors

* [@yitzwillroth](https://github.com/yitzwillroth) made their first contribution in https://github.com/laravel/boost/pull/43
* [@mpociot](https://github.com/mpociot) made their first contribution in https://github.com/laravel/boost/pull/61

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.2...v1.0.3

## [v1.0.2](https://github.com/laravel/boost/compare/v1.0.1...v1.0.2) - 2025-08-13

### What's Changed

* update laravel/roster version by [@ashleyhindle](https://github.com/ashleyhindle) in https://github.com/laravel/boost/pull/42
* Update core.blade.php by [@meatpaste](https://github.com/meatpaste) in https://github.com/laravel/boost/pull/41

### New Contributors

* [@meatpaste](https://github.com/meatpaste) made their first contribution in https://github.com/laravel/boost/pull/41

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.1...v1.0.2

## [v1.0.1](https://github.com/laravel/boost/compare/v1.0.0...v1.0.1) - 2025-08-13

**Full Changelog**: https://github.com/laravel/boost/compare/v1.0.0...v1.0.1

## [v1.0.0](https://github.com/laravel/boost/compare/v0.1.0...v1.0.0) - 2025-08-13

- Initial release of Laravel Boost.

## v0.1.0 (202x-xx-xx)

Initial pre-release.
