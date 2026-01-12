---
name: validating-skills
description: Validates Agent Skills using skills-ref. Use when checking SKILL.md frontmatter or naming conventions.
---

# Validating Skills

## Commands

```bash
# Validate a skill
skills-ref validate ./path/to/skill

# Read skill properties as JSON
skills-ref read-properties ./path/to/skill

# Generate <available_skills> XML for prompts
skills-ref to-prompt ./skill-a ./skill-b
```

## Frontmatter Requirements

| Field | Required | Rules |
|-------|----------|-------|
| `name` | Yes | Lowercase, hyphens, max 64 chars |
| `description` | Yes | Max 1024 chars |

## Naming Conventions

- Use gerund form: `building-livewire-components`, `testing-with-pest`
- Lowercase kebab-case only
- Name should match directory name

## Best Practices

**Be concise**: Claude is smart. Only add context it doesn't already have. Keep SKILL.md under 500 lines.

**Write good descriptions**: Include what the skill does AND when to use it. Use third person ("Processes files" not "I can help").

**Progressive disclosure**: Put essential info in SKILL.md, detailed reference in separate files. Keep references one level deep.

**Use workflows**: Break complex tasks into clear steps with checklists for multi-step operations.

**Avoid**:
- Time-sensitive info (use "old patterns" section instead)
- Windows-style paths (use forward slashes)
- Too many options (provide a default with escape hatch)
- Vague names: `helper`, `utils`, `tools`

**Test**: Validate with `skills-ref validate`, test with real usage across models.