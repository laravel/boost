# Laravel Boost v2: Agent Skills PRD

## 1. Overview

### 1.1 Problem Statement

Laravel Boost v1 relies on a single, fully composed guideline file (`CLAUDE.md` / `AGENTS.md`) that eagerly loads all instructions upfront, regardless of relevance. This causes:

- **Context bloat**: All guidelines loaded even when only working on a specific domain
- **Poor modularity**: Can't activate/deactivate specific knowledge domains
- **Reduced instruction relevance**: Generic guidance when specific guidance is needed

### 1.2 Solution

Introduce **Agent Skills** support in Laravel Boost v2:

- **Guidelines** become short, targeted, and global (core conventions only)
- **Skills** carry detailed, domain-specific knowledge (activated on demand)
- Skills allow explicit, thorough instructions without bloating context

### 1.3 User Experience (Unchanged)

From a user's perspective, nothing changes:
- Users still run `boost:install`
- The existing `.ai` override model continues to work
- Customization behaves exactly as it does today

---

## 2. Scope

### 2.1 In Scope

| Item | Description |
|------|-------------|
| Skills infrastructure | Already implemented in current branch |
| Skills content creation | SKILL.md or SKILL.blade.php files for package-specific domains |
| Blade rendering for skills | Render SKILL.blade.php to SKILL.md (same as guidelines) |
| Lightweight guidelines | Refactor existing guidelines to be concise |
| Skills activation guidance | Add activation hints to core guidelines |
| Automatic skill installation | All available skills installed automatically |
| Third-party skill detection | Already implemented, needs documentation |
| Skills override model | `.ai/skills/` for user customization |

### 2.2 Out of Scope (No-gos)

| Item | Rationale |
|------|-----------|
| Moving guidelines to package repos | Future migration, not part of v2 |
| Skills for agents without support | PhpStorm (Junie) use guidelines only |
| Laravel itself as a skill | Core Laravel stays in guidelines |

---

## 3. Agent Support Matrix

### 3.1 Skills-Capable Agents

| Agent | Interface | Skills Path | Status |
|-------|-----------|-------------|--------|
| Claude Code | `SkillsAgent` | `.claude/skills/` | ✅ Supported |
| Cursor | `SkillsAgent` | `.cursor/skills/` | ✅ Supported |
| Copilot | `SkillsAgent` | `.github/copilot/skills/` | ✅ Supported |
| Codex | `SkillsAgent` | `.codex/skills/` | ✅ Supported |
| OpenCode | `SkillsAgent` | `.opencode/skills/` | ✅ Supported |
| Gemini | `SkillsAgent` | `.gemini/skills/` | ✅ Supported (needs implementation) |

### 3.2 Guidelines-Only Agents

| Agent | Interface | Guidelines Path | Status |
|-------|-----------|-----------------|--------|
| PhpStorm (Junie) | `Agent` | `.junie/guidelines.md` | ✅ Guidelines only |
| VS Code | `McpClient` only | N/A | MCP only (no guidelines) |

### 3.3 Dropped Support

| Agent | Reason | Migration Path |
|-------|--------|----------------|
| None | All existing agents retained | N/A |

---

## 4. Skills Specification

### 4.1 Specification Compliance

Skills MUST follow the [agentskills.io specification](https://agentskills.io/specification):

- **Format**: Markdown (`.md`) or Blade template (`.blade.php`) that renders to Markdown
- **Entry file**: `SKILL.md` or `SKILL.blade.php` in skill directory
- **Blade rendering**: If `SKILL.blade.php` exists, it is compiled to `SKILL.md` during installation (same pipeline as guidelines)
- **Frontmatter**: YAML with required `name` and `description` fields

### 4.2 Naming Conventions

| Rule | Example | Rationale |
|------|---------|-----------|
| Gerund form | `building-livewire-components` | Describes action being performed |
| Lowercase kebab-case | `testing-with-pest` | URL-safe, consistent |
| Max 64 characters | `using-pennant-features` | Spec requirement |
| Directory name = skill name | Match exactly | Validation requirement |

**Avoid**: `helper`, `utils`, `tools`, `misc` (too vague)

### 4.3 Frontmatter Requirements

```yaml
---
name: building-livewire-components          # Required, lowercase kebab-case, max 64 chars
description: >-                             # Required, max 1024 chars
  Build reactive UI components with Livewire.
  Use when creating, modifying, or debugging Livewire components.
---
```

### 4.4 SKILL.md / SKILL.blade.php Structure

Skills can be written as either `SKILL.md` (plain Markdown) or `SKILL.blade.php` (Blade template that renders to Markdown). Blade templates support the same rendering features as guidelines (version detection, package context, etc.).

```markdown
---
name: <skill-name>
description: <what AND when to use>
---

# <Skill Title>

## When to Use This Skill

[Clear activation criteria - helps agents decide when to load this skill]

## Core Patterns

[Essential patterns the agent needs to know]

## Version-Specific Guidance

[Major version differences if applicable]

## Common Pitfalls

[Anti-patterns and mistakes to avoid]

## References

[Links to detailed reference files in references/ subdirectory]
```

**Blade template example**:
```blade
---
name: building-livewire-components
description: Build reactive UI components with Livewire
---

# Building Livewire Components

@if($assist->hasPackage('livewire/livewire', '^3.0'))
## Livewire 3 Patterns
[Version 3 specific content]
@elseif($assist->hasPackage('livewire/livewire', '^2.0'))
## Livewire 2 Patterns
[Version 2 specific content]
@endif
```

### 4.5 Progressive Disclosure (Gemini Model)

Gemini CLI implements a **progressive disclosure** model for skills:

1. **Discovery Phase**: Only skill names and descriptions are injected at session start
2. **Activation Trigger**: Model identifies matching tasks and calls `activate_skill` tool
3. **Consent Checkpoint**: User sees skill name, purpose, and directory path
4. **Injection**: Upon approval, full SKILL.md body and folder structure are loaded
5. **Execution**: Model proceeds with specialized guidance active

**Benefit**: Conserves context tokens by loading full content only when needed.

**Implication for Boost**: Skills should be written with clear, descriptive frontmatter since that's all the agent sees initially.

### 4.6 Reference Files

Skills can include additional files in a `references/` subdirectory. Reference files can be either `.md` or `.blade.php`:

```
.ai/livewire/building-livewire-components/
├── SKILL.md                      # Main skill file (< 500 lines)
│   # OR SKILL.blade.php (rendered to SKILL.md)
└── references/
    ├── v2-patterns.md            # Version-specific details
    ├── v3-patterns.blade.php     # Blade template (rendered to .md)
    └── testing.md                # Extended testing patterns
```

**Best practices**:
- Keep SKILL.md/SKILL.blade.php under 500 lines
- Use references for detailed, less-frequently-needed content
- One level deep only (no nested subdirectories)
- Blade templates in references are also rendered to Markdown during installation

---

## 5. Skills Catalog

### 5.1 Skills to Create

| Skill Name | Package | Priority | Source Guidelines |
|------------|---------|----------|-------------------|
| `building-livewire-components` | livewire | P0 | `.ai/livewire/*.blade.php` |
| `testing-with-pest` | pest | P0 | `.ai/pest/*.blade.php` |
| `building-inertia-apps` | inertia-laravel | P0 | `.ai/inertia-*/*.blade.php` |
| `using-tailwindcss` | tailwindcss | P1 | `.ai/tailwindcss/*.blade.php` |
| `using-pennant-package` | pennant | P1 | `.ai/pennant/*.blade.php` |
| `using-fluxui` | fluxui-pro/free | P1 | `.ai/fluxui-*/*.blade.php` |
| `using-volt-components` | volt | P2 | `.ai/volt/*.blade.php` |
| `using-folio-routing` | folio | P2 | `.ai/folio/*.blade.php` |
| `building-mcp-servers` | mcp | P2 | `.ai/mcp/*.blade.php` |

### 5.2 Guidelines That Do NOT Become Skills

These guidelines remain as lightweight guidelines only and are **NOT** split into skills:

| Package | Location | Rationale |
|---------|----------|-----------|
| `boost` | `.ai/boost/core.blade.php` | Meta-guidance, always needed |
| `laravel` | `.ai/laravel/core.blade.php` | Framework fundamentals, always needed |
| `php` | `.ai/php/core.blade.php` | Language fundamentals, always needed |
| `pint` | `.ai/pint/core.blade.php` | Short, project-wide formatting rules |
| `sail` | `.ai/sail/core.blade.php` | Short, environment-specific config |
| `herd` | `.ai/herd/core.blade.php` | Short, environment-specific config |
| `foundation.blade.php` | `.ai/foundation.blade.php` | Core project context, always needed |

These guidelines are kept lightweight and remain in guidelines because they contain essential, always-needed information that should be available in every context.

### 5.3 Skills Content Guidelines

**Move TO skills**:
- Detailed implementation patterns (50+ lines)
- Version-specific code examples
- Testing patterns for specific packages
- Form handling patterns
- Component lifecycle details

**Keep IN guidelines**:
- Package presence detection (`This project uses Livewire 3`)
- Key conventions (< 10 lines)
- Pointers to skills (`Activate building-livewire-components for details`)
- Always-needed context

### 5.4 Guideline to Skill Migration Strategy

#### Overview

Current guidelines in `.ai/` will be migrated to skills in phases:

**Phase 1 (Initial - Minimal)**:
- Create skill directories with minimal content from existing guidelines
- Skills will initially contain the same content as current guidelines (no refactoring)
- Guidelines remain unchanged initially
- Goal: Establish skill structure and infrastructure

**Phase 2 (Future - Refactored)**:
- Split guidelines into lightweight guidelines + detailed skills
- Refactor content: move detailed patterns to skills, keep essentials in guidelines
- Improve skill content with better organization and examples
- Goal: Optimize context usage and improve activation guidance

#### Skill Structure Rule

**Any folder inside `.ai/` that contains a skill directory is considered a skill.**

Skills can exist at two levels:
1. **Root level**: `.ai/{package}/{skill-name}/` - Applies to all versions
2. **Version level**: `.ai/{package}/{version}/{skill-name}/` - Version-specific skill

**Version-specific skills take precedence**: If a skill exists in a version directory (e.g., `3/building-livewire-components/`), it should be placed at the version level, not the root level. Only the skill matching the installed package version will be installed.

Examples:
- `.ai/livewire/3/building-livewire-components/` → Version-specific skill (Livewire v3)
- `.ai/livewire/2/building-livewire-components/` → Version-specific skill (Livewire v2)
- `.ai/pest/testing-with-pest/` → Root-level skill (all Pest versions)
- `.ai/inertia-vue/2/building-inertia-apps/` → Version-specific skill (Inertia v2)

The presence of a skill directory (containing `SKILL.md` or `SKILL.blade.php`) within a package folder or version folder indicates that package has skills available.

#### Guidelines That Remain Unchanged

These guidelines will **NOT** be split into skills and remain as lightweight guidelines only:

| Package | Reason |
|--------|--------|
| `boost` | Meta-guidance, always needed |
| `laravel` | Framework fundamentals, always needed |
| `pint` | Short, project-wide formatting rules |
| `sail` | Short, environment-specific config |
| `herd` | Short, environment-specific config |
| `php` | Language fundamentals, always needed |
| `foundation.blade.php` | Core project context, always needed |

#### Guidelines That Will Be Split

These guidelines will be split into lightweight guidelines + skills:

| Package | Guideline Content | Skill Content |
|---------|------------------|---------------|
| `livewire` | Package detection, basic command usage (~10 lines) | Component patterns, lifecycle hooks, testing, reactivity details |
| `pest` | Package detection, basic test command (~10 lines) | Assertions, mocking, datasets, test patterns |
| `inertia-*` | Package detection, basic navigation (~5 lines) | Form handling, page components, state management, SPA patterns |
| `tailwindcss` | Package detection, basic usage (~5 lines) | Spacing patterns, dark mode, component extraction, utility patterns |
| `pennant` | Package detection (~3 lines) | Feature flag patterns, evaluation strategies |
| `fluxui-*` | Package detection (~3 lines) | Component usage, styling patterns |
| `volt` | Package detection (~3 lines) | Single-file component patterns, Alpine integration |
| `folio` | Package detection (~3 lines) | File-based routing patterns |
| `mcp` | Package detection (~3 lines) | MCP server patterns, tool/resource creation |

#### Migration Process Example: Livewire

**Phase 1 (Initial - Minimal)**:

**Current `.ai/livewire/core.blade.php`** (49 lines) - Remains unchanged:
- Package detection
- Artisan command usage
- Best practices (detailed)
- Lifecycle hooks (detailed examples)
- Testing patterns (detailed examples)
- Wire directives (detailed)

**New `.ai/livewire/3/building-livewire-components/SKILL.md`** - Minimal skill (version-specific):
- Copy of current guideline content (49 lines)
- Same content, minimal changes
- Placed at version level (3/) since Livewire has version-specific patterns
- Goal: Establish skill structure

**Phase 2 (Future - Refactored)**:

**`.ai/livewire/core.blade.php` (~10-15 lines)** - Lightweight guideline:
```blade
## Livewire

This project uses Livewire {{ $assist->packageVersion('livewire/livewire') }}.

- Use the `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}` command to create components.
- State lives on the server; UI reflects it.
- All Livewire requests hit Laravel backend; validate and authorize in actions.

**For detailed Livewire patterns**, activate the `building-livewire-components` skill.
```

**`.ai/livewire/3/building-livewire-components/SKILL.md` (~300+ lines)** - Refactored skill (version-specific):
- Component creation patterns (v3-specific)
- State management strategies
- Lifecycle hooks (detailed)
- Reactivity patterns (wire:model.live, $this->dispatch())
- Testing Livewire components (detailed)
- Wire directive usage
- Common pitfalls
- Version 3 specific guidance

**Note**: If skills are version-specific, they should be placed at the version level (e.g., `3/building-livewire-components/`), not at the root level. This ensures only the correct version's skills are installed.

#### Content Distribution Rules

**Phase 1 (Initial - Minimal)**:
- Skills will contain the same content as current guidelines
- No refactoring or content splitting
- Guidelines remain unchanged
- Goal: Establish skill infrastructure

**Phase 2 (Future - Refactored)** - Content distribution:

**Move TO skills** (detailed content):
- Implementation patterns (50+ lines)
- Code examples with multiple variations
- Testing strategies and patterns
- Version-specific differences
- Common pitfalls and anti-patterns
- Advanced usage patterns
- Form handling details
- Component lifecycle details

**Keep IN guidelines** (essential only):
- Package presence detection (1-2 lines)
- Basic command usage (1-2 lines)
- Key conventions (< 5 lines)
- Pointer to skill activation (1 line)
- Always-needed context

#### Folder Structure Detection

The skill discovery mechanism (`GuidelineComposer::getBoostSkills()`) scans `.ai/{package}/` directories:
- If a subdirectory contains `SKILL.md` or `SKILL.blade.php`, it's a skill
- Skills can exist at root level (`.ai/{package}/{skill}/`) or version level (`.ai/{package}/{version}/{skill}/`)
- **Version-specific skills**: If a skill exists in a version directory, only install skills matching the installed package version
- **Root-level skills**: Install if no version-specific skill exists for that version

Example structure (version-specific skills):
```
.ai/livewire/
├── core.blade.php                    # Lightweight guideline
├── 2/
│   ├── core.blade.php                # Version-specific guideline
│   └── building-livewire-components/ # Version-specific skill (v2)
│       ├── SKILL.md
│       └── references/
│           └── v2-patterns.md
└── 3/
    ├── core.blade.php                # Version-specific guideline
    └── building-livewire-components/ # Version-specific skill (v3)
        ├── SKILL.md
        └── references/
            └── v3-patterns.md
```

Example structure (root-level skill):
```
.ai/pest/
├── core.blade.php                    # Lightweight guideline
├── 4/core.blade.php                  # Version-specific guideline
└── testing-with-pest/                # Root-level skill (all versions)
    ├── SKILL.md
    └── references/
        └── assertions.md
```

---

## 6. Directory Structure

### 6.1 Current Structure (Guidelines Only)

```
.ai/
├── foundation.blade.php
├── boost/core.blade.php
├── laravel/
│   ├── core.blade.php
│   ├── 10/core.blade.php
│   ├── 11/core.blade.php
│   └── 12/core.blade.php
├── livewire/
│   ├── core.blade.php          # 49 lines of detailed patterns
│   ├── 2/core.blade.php
│   └── 3/core.blade.php
├── pest/
│   ├── core.blade.php          # 53 lines of detailed patterns
│   └── 4/core.blade.php
└── ...
```

### 6.2 Target Structure (Guidelines + Skills)

```
.ai/
├── foundation.blade.php        # Add skills activation section
├── boost/core.blade.php        # NO CHANGES - stays as guideline only
├── laravel/                    # NO CHANGES - stays as guidelines only
│   ├── core.blade.php
│   └── ...
├── livewire/
│   ├── core.blade.php          # SLIM: ~15 lines, pointer to skill
│   ├── 2/
│   │   ├── core.blade.php      # Version-specific guideline
│   │   └── building-livewire-components/   # Version-specific skill (v2)
│   │       ├── SKILL.md                     # OR SKILL.blade.php
│   │       └── references/
│   │           └── v2-patterns.md
│   └── 3/
│       ├── core.blade.php      # Version-specific guideline
│       └── building-livewire-components/   # Version-specific skill (v3)
│           ├── SKILL.md                     # OR SKILL.blade.php
│           └── references/
│               └── v3-patterns.md
├── pest/
│   ├── core.blade.php          # SLIM: ~15 lines
│   └── testing-with-pest/      # NEW: Skill directory
│       ├── SKILL.md                     # OR SKILL.blade.php (rendered to SKILL.md)
│       └── references/
│           └── assertions.md            # OR .blade.php
└── ...
```

**Skill Detection Rule**: 
- Any folder inside `.ai/{package}/` that contains `SKILL.md` or `SKILL.blade.php` is considered a skill directory
- Skills can exist at root level (`.ai/{package}/{skill}/`) or version level (`.ai/{package}/{version}/{skill}/`)
- **Version-specific skills**: If a skill exists in a version directory, only install skills matching the installed package version
- **Root-level skills**: Install if no version-specific skill exists for that version
- If skills are version-specific, they should be placed at the version level (not root level) to ensure correct version installation

### 6.3 User Override Structure

Users can override skills in three ways (in priority order):

**1. Explicit skill override directory** (highest priority):
```
Project Root/
├── .ai/
│   └── skills/                 # Explicit skill override directory
│       └── building-livewire-components/
│           └── SKILL.md        # Overrides all built-in skills with this name
│           # OR SKILL.blade.php (rendered to SKILL.md)
```

**2. Version-specific skill override** (in project's `.ai` folder):
```
Project Root/
├── .ai/
│   └── livewire/
│       └── 3/                  # Version-specific override
│           └── building-livewire-components/
│               └── SKILL.md    # Overrides Boost's v3 skill
```

**3. Root-level skill override** (in project's `.ai` folder):
```
Project Root/
├── .ai/
│   ├── guidelines/             # Override guidelines (existing)
│   │   └── livewire/
│   │       └── core.blade.php  # Overrides built-in guideline
│   └── livewire/
│       └── building-livewire-components/
│           └── SKILL.md       # Overrides Boost's root-level skill
```

**Note**: User overrides in `.ai/{package}/` take precedence over Boost built-in skills, but `.ai/skills/` overrides take highest priority.

---

## 7. Override Model

### 7.1 Priority Order (Highest to Lowest)

```
1. User explicit skills      (.ai/skills/{name}/)                    → custom: true (explicit override)
2. User version skills       (.ai/{package}/{version}/{name}/)      → custom: true (user override in project)
3. User root skills          (.ai/{package}/{name}/)                → custom: true (user override in project)
4. Boost version skills       (package's .ai/{package}/{version}/{name}/) → custom: false (built-in version-specific)
5. Boost root skills          (package's .ai/{package}/{name}/)     → custom: false (built-in root level)
6. Third-party skills         (vendor/.../resources/boost/{name}/)  → custom: false
```

**Version Matching**: If a version-specific skill exists (e.g., `.ai/livewire/3/building-livewire-components/`), it takes precedence over root-level skills for that version. Only skills matching the installed package version are installed.

**User Overrides**: Users can override skills by creating files in their project's `.ai` folder:
- `.ai/skills/{name}/` - Explicit skill override directory (highest priority)
- `.ai/{package}/{version}/{name}/` - Override version-specific skill in their project
- `.ai/{package}/{name}/` - Override root-level skill in their project

### 7.2 Override Behavior

| Scenario | Result |
|----------|--------|
| User creates `.ai/skills/testing-with-pest/SKILL.md` or `SKILL.blade.php` | User explicit skill used, all built-in ignored |
| User creates `.ai/livewire/3/building-livewire-components/SKILL.md` in project | User version-specific skill used, Boost version skill ignored |
| User creates `.ai/livewire/building-livewire-components/SKILL.md` in project | User root skill used, Boost root skill ignored |
| Boost has `.ai/livewire/3/building-livewire-components/` and package is v3, no user override | Boost version-specific skill (v3) installed |
| Boost has `.ai/livewire/building-livewire-components/` (root) and package is v3, no v3-specific skill | Boost root-level skill installed for v3 |
| Third-party package provides `testing-with-pest` | Package skill used if no Boost or user skill |
| No override exists | Boost built-in skill used (version-specific preferred over root) |
| `SKILL.blade.php` exists | Rendered to `SKILL.md` during installation |

### 7.3 Guidelines Override (Unchanged)

Existing `.ai/guidelines/` override model continues to work:

```
1. User guidelines     (.ai/guidelines/{path})        → Override
2. Boost guidelines    (.ai/{path}.blade.php)         → Default
```

---

## 8. Installation Flow

### 8.1 Current Flow (v1)

```
boost:install
├── Detect environments
├── Select features (MCP, guidelines)
├── Select packages for guidelines
├── Write guidelines to agents
└── Configure MCP
```

### 8.2 Target Flow (v2)

```
boost:install
├── Detect environments
├── Select features (MCP, guidelines)
├── Select packages for guidelines
├── Write guidelines to agents
├── [NEW] Write all available skills to skills-capable agents (automatic)
└── Configure MCP
```

### 8.3 Automatic Skill Installation

All available skills are automatically installed to skills-capable agents:
- Skills are discovered from Boost built-in (`.ai/{package}/{skill}/` or `.ai/{package}/{version}/{skill}/`)
- **Version-specific skills**: If a skill exists in a version directory (e.g., `.ai/livewire/3/building-livewire-components/`), only install skills matching the installed package version
- **Root-level skills**: Install if no version-specific skill exists for that version
- Skills are discovered from third-party packages (`vendor/*/resources/boost/{skill}/`)
- User overrides in `.ai/skills/` take precedence
- No user interaction required - installation behavior unchanged from v1

---

## 9. Skills Activation Guidance

### 9.1 Problem

Agents may not reliably determine when to activate which skill.

### 9.2 Solution

Add explicit activation guidance to `foundation.blade.php`:

```markdown
## Skills Activation

When working on this project, determine which domain-specific skills are relevant:

### Frontend Development
- **Livewire project**: Activate `building-livewire-components` for component work
- **Inertia project**: Activate `building-inertia-apps` for SPA patterns
- **Styling work**: Activate `using-tailwindcss` for CSS/design tasks

### Testing
- **Test creation/modification**: Activate `testing-with-pest`

### Features & Flags
- **Feature toggles**: Activate `using-pennant-features`

To determine the frontend stack, check for:
- `livewire/livewire` in composer.json → Livewire project
- `inertiajs/inertia-laravel` in composer.json → Inertia project
```

### 9.3 Activation Criteria in Skills

Each SKILL.md includes a "When to Use This Skill" section:

```markdown
## When to Use This Skill

Activate this skill when:
- Creating new Livewire components
- Modifying existing component state or behavior
- Debugging reactivity or lifecycle issues
- Writing Livewire component tests
- Adding Alpine.js interactivity to components
```

---

## 10. Third-Party Package Skills

### 10.1 Discovery Mechanism

**Already implemented** in `Composer::packagesDirectoriesWithBoostSkills()`:

```php
// Scans: vendor/{package}/resources/boost/{skill-name}/SKILL.md
```

### 10.2 Package Author Requirements

Third-party packages can provide skills by:

1. Creating `resources/boost/{skill-name}/SKILL.md` or `SKILL.blade.php`
2. Following agentskills.io specification
3. Including required frontmatter
4. Blade templates are automatically rendered to Markdown during installation

### 10.3 Documentation for Package Authors

**To create**: Documentation guide for third-party package authors explaining:
- Directory structure
- SKILL.md or SKILL.blade.php format
- Blade rendering support (same as guidelines)
- Frontmatter requirements
- Best practices
- Testing their skills

---

## 11. Technical Implementation

### 11.1 Already Implemented (Current Branch)

| Component | File | Status |
|-----------|------|--------|
| Skill data class | `src/Install/Skill.php` | ✅ Complete |
| SkillWriter | `src/Install/SkillWriter.php` | ⚠️ Needs Blade rendering support |
| SkillsAgent interface | `src/Contracts/SkillsAgent.php` | ✅ Complete |
| Skills discovery | `src/Install/GuidelineComposer.php` | ⚠️ Needs SKILL.blade.php detection + version-specific skill matching |
| Third-party detection | `src/Support/Composer.php` | ✅ Complete |
| Install command integration | `src/Console/InstallCommand.php` | ✅ Complete |
| Agent implementations | 5 agents with `skillsPath()` | ✅ Complete |
| Unit tests | `tests/Unit/Install/Skill*.php` | ✅ Complete |
| Feature tests | `tests/Feature/Install/GuidelineComposerTest.php` | ✅ Complete |

### 11.2 To Be Implemented

| Component | File | Priority |
|-----------|------|----------|
| Skill content files | `.ai/{package}/{skill}/SKILL.md` | P0 |
| Activation guidance | `.ai/foundation.blade.php` | P0 |
| Gemini SkillsAgent implementation | `src/Install/CodeEnvironment/Gemini.php` | P0 |
| Lightweight guidelines | `.ai/{package}/core.blade.php` | P1 |
| Package author docs | `docs/skills-for-packages.md` | P2 |

### 11.3 Code Changes Required

#### `src/Install/SkillWriter.php`

Add Blade rendering support:
- Check for `SKILL.blade.php` in addition to `SKILL.md`
- Use `RendersBladeGuidelines` trait (same as guidelines)
- Render Blade templates to Markdown before copying to target directory
- Render Blade templates in `references/` subdirectory as well

#### `src/Install/GuidelineComposer.php`

Update `getBoostSkills()` and `parseSkill()` methods:
- Check for both `SKILL.md` and `SKILL.blade.php`
- Prefer `SKILL.blade.php` if both exist (same precedence as guidelines)
- **Version-specific skill detection**: Scan version directories (e.g., `2/`, `3/`) for skills
- **Version matching**: Only install skills matching the installed package version
- If version-specific skill exists, prefer it over root-level skill for that version
- If no version-specific skill exists, fall back to root-level skill

#### `src/Console/InstallCommand.php`

Update `installSkills()` to automatically install all discovered skills (no user selection required).

#### `.ai/foundation.blade.php`

Add skills activation section (see Section 9.2).

---

## 12. Migration & Breaking Changes

### 12.1 Breaking Changes

| Change | Impact | Migration |
|--------|--------|-----------|
| Guidelines become shorter | Less context by default | Skills provide details on demand |
| Skills folder created | New files in project | Add to `.gitignore` if desired |

### 12.2 Backward Compatibility

| Feature | Status |
|---------|--------|
| `.ai/guidelines/` override | ✅ Unchanged |
| `boost:install` command | ✅ Unchanged (no new prompts) |
| MCP configuration | ✅ Unchanged |
| Agent detection | ✅ Unchanged |

### 12.3 Migration Guide

For users upgrading from v1:

1. Run `boost:install` (will detect existing config)
2. Skills automatically installed alongside existing guidelines
3. Override skills in `.ai/skills/` if needed

---

## 13. Risks & Mitigations

### 13.1 Skill Activation Reliability

**Risk**: Agents may not reliably activate the right skill at the right time.

**Mitigations**:
- Clear activation guidance in `foundation.blade.php`
- Explicit "When to Use" section in every SKILL.md
- Descriptive skill names and descriptions
- Package detection hints (check composer.json)

### 13.2 Context Reduction Quality Impact

**Risk**: Shorter guidelines may reduce output quality initially.

**Mitigations**:
- Phased rollout: P0 skills first, gather feedback
- Keep essential patterns in guidelines
- Clear skill activation triggers
- User can override to add back content

### 13.3 Skill Discovery Edge Cases

**Risk**: Skills not discovered from unconventional package structures.

**Mitigations**:
- Clear documentation for package authors
- Validation tooling (`skills-ref validate`)
- Fallback to guideline content if skill missing

---

## 14. Success Metrics

### 14.1 Quantitative

| Metric | Target | Measurement |
|--------|--------|-------------|
| Context reduction | 50%+ for focused tasks | Compare token counts |
| Guideline file size | < 30 lines per file | Line count |
| Skill activation rate | > 80% accuracy | User feedback |

### 14.2 Qualitative

| Metric | Target |
|--------|--------|
| Installation UX | Unchanged from v1 (no new prompts) |
| Skill content quality | At least as good as current guidelines |
| Override model clarity | Users understand how to customize |

---

## 15. Timeline

### Phase 1: P0 Skills (Week 1-2)

- [ ] Create `building-livewire-components` skill
- [ ] Create `testing-with-pest` skill
- [ ] Create `building-inertia-apps` skill
- [ ] Add skills activation guidance to `foundation.blade.php`
- [ ] Test skill activation in Claude Code

### Phase 2: P1 Skills + Guidelines (Week 3-4)

- [ ] Create P1 skills (Tailwind, Pennant, FluxUI)
- [ ] Refactor guidelines to lightweight versions
- [ ] Update tests

### Phase 3: P2 Skills + Docs (Week 5-6)

- [ ] Create P2 skills (Volt, Folio, MCP)
- [ ] Create package author documentation
- [ ] CHANGELOG and migration guide
- [ ] Final testing and release

---

## 16. Appendix

### A. Current Guidelines Line Counts

| File | Lines | Target After Refactor |
|------|-------|----------------------|
| `livewire/core.blade.php` | 49 | ~15 |
| `pest/core.blade.php` | 53 | ~15 |
| `inertia-vue/2/forms.blade.php` | 71 | ~10 |
| `tailwindcss/core.blade.php` | 22 | ~10 |
| `foundation.blade.php` | 40 | ~60 (adds activation) |

### B. Skills File Structure Example

```
.ai/livewire/building-livewire-components/
├── SKILL.md (300 lines)                    # OR SKILL.blade.php
│   ├── Frontmatter (name, description)
│   ├── When to Use This Skill
│   ├── Component Creation
│   ├── State Management
│   ├── Lifecycle Hooks
│   ├── Reactivity Patterns
│   ├── Testing Components
│   └── Common Pitfalls
└── references/
    ├── v2-patterns.md (100 lines)         # OR .blade.php
    └── v3-patterns.md (100 lines)         # OR .blade.php
    # Blade templates render to .md during installation
```

### C. Related Files

| File | Purpose |
|------|---------|
| `src/Install/Skill.php` | Skill data object |
| `src/Install/SkillWriter.php` | Writes skills to agent paths |
| `src/Install/GuidelineComposer.php` | Discovers skills and guidelines |
| `src/Contracts/SkillsAgent.php` | Interface for skill-capable agents |
| `src/Console/InstallCommand.php` | Installation orchestration |
| `.ai/foundation.blade.php` | Core guidelines (needs activation section) |
