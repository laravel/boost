---
name: formatting-with-pint
description: Code formatting with Laravel Pint. Use when configuring code style, fixing formatting issues, or setting up CI/CD formatting checks.
---

# Formatting with Pint

## When to use this skill

Use this skill when the user asks about:
- Formatting PHP code
- Configuring code style rules
- Setting up Pint in CI/CD
- Custom formatting presets

## Basic Usage

```bash
# Format all files
./vendor/bin/pint

# Format specific files/directories
./vendor/bin/pint app/Models
./vendor/bin/pint app/Http/Controllers/UserController.php

# Preview changes without applying
./vendor/bin/pint --test

# Show diff of changes
./vendor/bin/pint -v
```

## Configuration

Create `pint.json` in project root:

```json
{
    "preset": "laravel",
    "rules": {
        "array_syntax": {
            "syntax": "short"
        },
        "binary_operator_spaces": {
            "default": "single_space"
        }
    }
}
```

## Presets

Available presets:

```json
{
    "preset": "laravel"
}
```

```json
{
    "preset": "psr12"
}
```

```json
{
    "preset": "symfony"
}
```

```json
{
    "preset": "per"
}
```

## Common Rules

### Array Syntax

```json
{
    "rules": {
        "array_syntax": {
            "syntax": "short"
        }
    }
}
```

Before: `array('foo' => 'bar')`
After: `['foo' => 'bar']`

### Blank Lines

```json
{
    "rules": {
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        },
        "no_extra_blank_lines": {
            "tokens": ["extra", "throw", "use"]
        }
    }
}
```

### Import Ordering

```json
{
    "rules": {
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "no_unused_imports": true
    }
}
```

### Operators

```json
{
    "rules": {
        "binary_operator_spaces": {
            "default": "single_space",
            "operators": {
                "=>": "align_single_space_minimal"
            }
        },
        "concat_space": {
            "spacing": "one"
        },
        "not_operator_with_successor_space": true
    }
}
```

### Braces and Spacing

```json
{
    "rules": {
        "braces": {
            "position_after_functions_and_oop_constructs": "next"
        },
        "method_chaining_indentation": true,
        "no_spaces_around_offset": true
    }
}
```

## Including/Excluding Files

```json
{
    "preset": "laravel",
    "exclude": [
        "bootstrap",
        "storage",
        "vendor"
    ],
    "notPath": [
        "tests/Fixtures"
    ],
    "notName": [
        "*-stub.php"
    ]
}
```

## Strict Rules

Enable stricter formatting:

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "strict_comparison": true,
        "strict_param": true
    }
}
```

## Full Example Configuration

```json
{
    "preset": "laravel",
    "rules": {
        "array_syntax": {
            "syntax": "short"
        },
        "binary_operator_spaces": {
            "default": "single_space",
            "operators": {
                "=>": "align_single_space_minimal"
            }
        },
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        },
        "concat_space": {
            "spacing": "one"
        },
        "declare_strict_types": true,
        "method_argument_space": {
            "on_multiline": "ensure_fully_multiline"
        },
        "no_unused_imports": true,
        "not_operator_with_successor_space": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "single_quote": true,
        "trailing_comma_in_multiline": {
            "elements": ["arrays", "arguments", "parameters"]
        }
    },
    "exclude": [
        "bootstrap",
        "storage",
        "vendor"
    ]
}
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Code Style

on: [push, pull_request]

jobs:
  pint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install

      - name: Run Pint
        run: ./vendor/bin/pint --test
```

### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit

FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -n "$FILES" ]; then
    ./vendor/bin/pint $FILES
    git add $FILES
fi
```

## Composer Scripts

```json
{
    "scripts": {
        "lint": "./vendor/bin/pint",
        "lint:test": "./vendor/bin/pint --test"
    }
}
```

Run with:
```bash
composer lint
composer lint:test
```

## IDE Integration

### VS Code

Install "Laravel Pint" extension, add to settings:

```json
{
    "editor.formatOnSave": true,
    "laravel-pint.enable": true
}
```

### PhpStorm

Configure as external tool:
- Program: `$ProjectFileDir$/vendor/bin/pint`
- Arguments: `$FilePath$`
- Working directory: `$ProjectFileDir$`

## Best Practices

1. **Use Laravel preset** - Matches framework conventions
2. **Run in CI** - Catch style issues early
3. **Format on save** - Keep code consistent
4. **Commit pint.json** - Share config with team
5. **Exclude generated files** - Skip bootstrap, storage
6. **Use --test in CI** - Fail on style violations
