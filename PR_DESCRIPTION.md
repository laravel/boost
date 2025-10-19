# Add Automatic Laravel Sail Detection for MCP Server Configuration

## Summary

This PR adds automatic detection of Laravel Sail environments and configures the MCP server to use Sail when detected. This ensures the MCP server runs inside the Docker container with the correct PHP version and environment, rather than using the host machine's PHP installation.

## Motivation

Currently, users running Laravel Boost in Sail projects must manually configure the MCP server to use `./vendor/bin/sail` instead of `php`. This PR makes the detection automatic, improving the developer experience for Sail users.

### Problem

When Sail users run `php artisan boost:install` or `php artisan boost:update`, the generated MCP configuration uses:

```json
{
    "mcpServers": {
        "laravel-boost": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

This runs the MCP server on the host machine, which may have:
- Different PHP version than the container
- Missing PHP extensions
- Different environment configuration

### Solution

With this PR, Sail projects automatically get:

```json
{
    "mcpServers": {
        "laravel-boost": {
            "command": "./vendor/bin/sail",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

## Changes

### Core Implementation

**File:** `src/Install/CodeEnvironment/CodeEnvironment.php`

- Added `isSailProject()` method to detect Sail by checking for:
  - `vendor/bin/sail` executable
  - `docker-compose.yml` file
- Modified `getPhpPath()` to return `./vendor/bin/sail` when Sail is detected
- Modified `getArtisanPath()` to return `artisan` when Sail is detected
- Added `fileExists()` helper method for improved testability

### Tests

**Unit Tests:** `tests/Unit/Install/CodeEnvironment/CodeEnvironmentTest.php`
- ✅ `getPhpPath returns sail path when project uses Laravel Sail`
- ✅ `getArtisanPath returns relative artisan when project uses Laravel Sail`
- ✅ `isSailProject returns true when both sail and docker-compose exist`
- ✅ `isSailProject returns false when sail does not exist`
- ✅ `isSailProject returns false when docker-compose does not exist`

**Integration Tests:** `tests/Feature/Install/SailDetectionTest.php`
- ✅ `ClaudeCode detects Sail when both sail and docker-compose exist`
- ✅ `ClaudeCode uses php when Sail is not detected`
- ✅ `ClaudeCode uses php when only sail exists but no docker-compose`
- ✅ `ClaudeCode uses php when only docker-compose exists but no sail`

### Documentation

**File:** `README.md`

- Added installation note for Sail users in the Installation section
- Added dedicated "Laravel Sail Support" section with:
  - Explanation of automatic detection
  - Detection criteria
  - Configuration examples
  - Benefits of running inside Docker container

## Backward Compatibility

✅ **100% backward compatible** - Projects without Sail continue to work exactly as before using `php artisan boost:mcp`.

## Commands Affected

- ✅ `php artisan boost:install` - Automatically detects and configures Sail
- ✅ `php artisan boost:update` - Automatically detects and configures Sail (calls `InstallCommand` internally)

## Test Results

```
Tests:    9 passed (13 assertions) - Sail-specific tests
Tests:  307 passed (1354 assertions) - Full test suite
Code Style: PASS (108 files) - Laravel Pint
Static Analysis: PASS (67 files) - PHPStan Level 5
```

## Checklist

- [x] Tests added for new functionality
- [x] All tests passing
- [x] Code style verified with Pint
- [x] Static analysis passing (PHPStan)
- [x] Documentation updated
- [x] Backward compatible
- [x] No breaking changes

## Additional Notes

The detection is automatic and transparent to users. When both `vendor/bin/sail` and `docker-compose.yml` exist in the project root, Boost automatically uses Sail. No manual configuration required.

This enhancement benefits all Sail users across all supported IDEs (Claude Code, Cursor, VS Code, PhpStorm, GitHub Copilot, etc.).
