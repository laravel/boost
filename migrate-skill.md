# Guideline to Skill Migration Guide

This guide explains how to migrate detailed guidelines from `core.blade.php` files into standalone skill files, following the pattern established in Laravel Boost.

## Overview

**Guidelines** (`core.blade.php`) are brief, high-level instructions that appear in every AI conversation. **Skills** are detailed, actionable guides that are activated on-demand when specific tasks are needed.

The migration process involves:
1. Extracting detailed content from `core.blade.php`
2. Creating a structured skill file with that content
3. Updating `core.blade.php` to reference the skill

## When to Create a Skills

Create a skill when:
- The guideline has detailed implementation patterns, code examples, or extensive instructions
- The content is only relevant for specific tasks (not every conversation)
- You want to provide deep guidance without cluttering the core guidelines
- The topic has multiple subsections, examples, or common pitfalls

Keep as a core guideline when:
- The instruction is brief and universally applicable
- It's critical for every conversation (like linting or testing requirements)
- It's a simple directive without much elaboration

## File Structure

### Before Migration
```
.ai/package-name/
  └── core.blade.php (contains detailed instructions)
```

### After Migration
```
.ai/package-name/
  ├── core.blade.php (brief, points to skill)
  └── skill-name/
      └── SKILL.md (or SKILL.blade.php)
```

## Migration Steps

### Step 1: Identify Content for Extraction

Look at your `core.blade.php` and identify:
- Detailed implementation patterns
- Code examples and snippets
- Multiple subsections or concepts
- Content that's only relevant for specific tasks

**Example from MCP before migration:**
```blade
## Laravel MCP

- MCP (Model Context Protocol) is very new. You must use the `search-docs` tool...
- Tools are functions that the AI can call...
- Resources expose data to the AI...
- [Detailed examples, patterns, testing instructions...]
```

### Step 2: Create the Skill Directory and File

1. Create a directory for the skill: `.ai/package-name/skill-name/`
2. Create the skill file:
   - Use `SKILL.md` if you don't need Blade features (no `$assist` helpers)
   - Use `SKILL.blade.php` if you need Blade (for `$assist->artisanCommand()`, conditionals, etc.)

**Naming Convention:**
- Use kebab-case for directory names
- Skill names should be action-oriented: `building-*`, `using-*`, `testing-*`

### Step 3: Write the Skill File

#### Frontmatter (Required)

All skills must start with YAML frontmatter:

```markdown
---
name: skill-name
description: >-
  Brief one-liner about what this skill does.
  Continue description on multiple lines if needed.
---
```

**Guidelines:**
- `name`: Use kebab-case, same as the directory name
- `description`: Clearly state what the skill does and when to use it (2-3 lines max)

#### Structure (Recommended Sections)

```markdown
# Skill Title

## When to Use This Skill

Activate this skill when:
- Specific scenario 1
- Specific scenario 2
- Specific scenario 3

## Core Patterns

### Subsection 1
[Explanation and examples]

### Subsection 2
[Explanation and examples]

## Documentation

Use the `search-docs` tool for [specific topic] documentation.

## Best Practices

- Best practice 1
- Best practice 2

## Testing

[Testing patterns and examples]

## Common Pitfalls

- Common mistake 1 (and how to avoid it)
- Common mistake 2 (and how to avoid it)
```

### Step 4: Choose File Format (.md vs .blade.php)

#### Use SKILL.md when:
- Content is pure markdown
- No need for dynamic content or conditionals
- No need for `$assist` helper methods
- Examples: MCP, Flux UI, Pennant

**Example (SKILL.md):**
```markdown
---
name: building-mcp-servers
description: >-
  Build MCP servers, tools, and resources.
---
# Building MCP Servers

## When to Use This Skill

Activate this skill when:
- Creating new MCP tools
- Setting up MCP server routes

## Core Patterns

```php
use Laravel\Mcp\Server\Tool;

class MyTool extends Tool {
    // Implementation
}
```

## Common Pitfalls

- Not using `search-docs` for latest documentation
```

#### Use SKILL.blade.php when:
- You need `$assist` helpers (for Artisan commands, package detection, etc.)
- You need conditional content based on project setup
- You want to use Blade directives like `@verbatim`, `@if`, `@boostsnippet`
- Examples: Livewire, Inertia, Volt, Folio

**Example (SKILL.blade.php):**
```blade
---
name: building-livewire-components
description: >-
  Build reactive UI components with Livewire.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Building Livewire Components

## When to Use This Skill

Activate this skill when:
- Creating new Livewire components

## Core Patterns

Use the `{{ $assist->artisanCommand('make:livewire [ComponentName]') }}` command.

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::ALPINE))
Alpine.js is available in this project.
@endif

## Common Pitfalls

- Using `wire:model` expecting real-time updates
```

### Step 5: Update core.blade.php

After creating the skill, update the core guideline to:
1. Keep only essential, always-needed information
2. Reference the skill for detailed guidance

**Before:**
```blade
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Package Name

- [Long detailed instructions...]
- [Implementation patterns...]
- [Multiple code examples...]
- [Testing instructions...]
```

**After:**
```blade
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Package Name

- Brief essential instruction that applies to every conversation.
- Use/activate 'skill-name' to [specific purpose].
```

**Real Example (MCP after migration):**
```blade
## Laravel MCP

- MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- use/activate 'building-mcp-servers' to properly build MCP servers.
```

## Real-World Example: MCP Migration

### Before Migration

**`.ai/mcp/core.blade.php` (hypothetical before state):**
```blade
## Laravel MCP

- MCP is very new. Use `search-docs` for documentation.
- Tools are functions AI can call to perform actions
- Resources expose data to the AI
- Tools extend Laravel\Mcp\Server\Tool
- Implement handle(Request): Response
- Register with Mcp::web() in routes/ai.php
- Do not run mcp:start, it hangs waiting for input
- Test using search-docs for testing instructions
[...more detailed content...]
```

### After Migration

**`.ai/mcp/core.blade.php`:**
```blade
## Laravel MCP

- MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- use/activate 'building-mcp-servers' to properly build MCP servers.
```

**`.ai/mcp/building-mcp-servers/SKILL.md`:**
```markdown
---
name: building-mcp-servers
description: >-
  Build MCP (Model Context Protocol) servers, tools, resources, and prompts.
  Use when working with AI integrations via MCP.
---
# Building MCP Servers

## When to Use This Skill

Activate this skill when:
- Creating new MCP tools, resources, or prompts
- Setting up MCP server routes
- Testing MCP functionality
- Debugging MCP connection issues

## Core Patterns

### Overview

MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.

### Registration

MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically, they will be registered using `Mcp::web()` to register an HTTP streaming MCP server.

```php
// routes/ai.php
use Laravel\Mcp\Facades\Mcp;

Mcp::web();
```

## Tools

Tools are functions that the AI can call to perform actions:

```php
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Request;
use Laravel\Mcp\Server\Response;

class MyTool extends Tool
{
    public function handle(Request $request): Response
    {
        // Tool implementation
        return new Response(['result' => 'success']);
    }
}
```

## Testing

Servers are very testable; use the `search-docs` tool to find testing instructions.

## Important Notes

- **Do not run `mcp:start`**. This command hangs waiting for JSON-RPC MCP requests.
- Some MCP clients use Node, which has its own certificate store. If a user tries to connect to their web MCP server locally using HTTPS, it could fail due to this reason. They will need to switch to HTTP during local development.

## Common Pitfalls

- Running `mcp:start` command (it hangs waiting for input)
- Using HTTPS locally with Node-based MCP clients
- Not using `search-docs` for the latest MCP documentation
- Not registering MCP server routes in `routes/ai.php`
- Do not mention ai.php in the bootstrap.php file it's already registered.
```

## Blade Features in Skills

When using `SKILL.blade.php`, you have access to:

### GuidelineAssist Helpers

```blade
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

<!-- Artisan commands -->
{{ $assist->artisanCommand('make:livewire [ComponentName]') }}

<!-- Binary commands -->
{{ $assist->binCommand('pint') }}

<!-- Package detection -->
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::ALPINE))
    Alpine is available.
@endif

<!-- Version detection -->
@if($assist->inertia()->hasFormComponent())
    Inertia form component is available.
@endif
```

### Blade Directives

```blade
<!-- Verbatim blocks (prevents Blade parsing) -->
@verbatim
<code-snippet name="Example" lang="php">
{{ $variable }} <!-- This won't be processed by Blade -->
</code-snippet>
@endverbatim

<!-- Boost snippets -->
@boostsnippet("Snippet Name", "php")
// Code here
@endboostsnippet

<!-- Conditionals -->
@if($condition)
    Content if true
@else
    Content if false
@endif
```

## Code Snippets in Skills

### Inline Code Blocks

For simple examples, use markdown code blocks:

````markdown
```php
$example = 'code';
```
````

### Code Snippets with Metadata

For more complex examples that need names:

````blade
@verbatim
<code-snippet name="Descriptive Name" lang="php">
public function example()
{
    return 'code';
}
</code-snippet>
@endverbatim
````

**Note:** Use `@verbatim` when including code snippets that contain Blade-like syntax (like `{{ }}` or `@directives`) to prevent Blade from processing them.

## Checklist for Migration

- [ ] Identified content that should be extracted into a skill
- [ ] Created skill directory with appropriate name (kebab-case, action-oriented)
- [ ] Chose correct file format (`.md` vs `.blade.php`)
- [ ] Added frontmatter with `name` and `description`
- [ ] Structured skill with clear sections:
  - [ ] When to Use This Skill
  - [ ] Core Patterns
  - [ ] Documentation references
  - [ ] Common Pitfalls (if applicable)
- [ ] Added code examples with proper formatting
- [ ] Used `@verbatim` for code snippets containing Blade syntax
- [ ] Updated `core.blade.php` to be brief and reference the skill
- [ ] Tested that skill name matches directory name
- [ ] Ensured skill description is concise and action-oriented

## Version-Specific Skills

For packages with different versions (like Laravel 10/11/12 or Livewire 2/3), create version-specific directories:

```
.ai/package-name/
  ├── core.blade.php
  ├── 2/
  │   ├── core.blade.php
  │   └── building-components/
  │       └── SKILL.blade.php
  └── 3/
      ├── core.blade.php
      └── building-components/
          └── SKILL.blade.php
```

The version-specific skill can have different content based on the version's features.

## Tips and Best Practices

1. **Keep core guidelines minimal** - Only include information needed in every conversation
2. **Make skills actionable** - Focus on "how to do X" rather than "what X is"
3. **Use consistent structure** - Follow the recommended sections for all skills
4. **Include search-docs references** - Encourage using the tool for latest documentation
5. **Add real code examples** - Show actual implementation patterns
6. **Document common mistakes** - Help prevent known pitfalls
7. **Use descriptive skill names** - Name should clearly indicate what the skill helps with
8. **Keep descriptions concise** - 2-3 lines maximum in frontmatter
9. **Test skill activation** - Ensure the skill name works with "use/activate 'skill-name'"
10. **Add "When to Use" section** - Be specific about activation scenarios

## Testing Your Migration

After migrating:

1. Check that `core.blade.php` is brief and points to the skill
2. Verify skill file has proper frontmatter 'skill-ref validate ../skills-folder' command
3. Ensure skill name matches directory name
4. Confirm all code examples are properly formatted
5. Test that Blade features work (if using `.blade.php`)
6. Run the skill through the Boost system to ensure it loads properly

## Additional Resources

- See `.ai/mcp/building-mcp-servers/SKILL.md` for a simple skill example
- See `.ai/livewire/3/building-livewire-components/SKILL.blade.php` for a Blade skill example
- See `.ai/inertia-laravel/2/building-inertia-apps/SKILL.blade.php` for complex conditionals
- Check `src/Install/Skill.php` for the Skill class structure
- Review `src/Install/GuidelineAssist.php` for available helper methods
