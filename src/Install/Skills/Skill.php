<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Skills;

/**
 * Value object representing an Agent Skill.
 *
 * @see https://agentskills.io/specification
 */
class Skill
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $content,
        public readonly array $metadata = [],
        public readonly ?string $path = null,
        public readonly bool $custom = false,
        public readonly bool $thirdParty = false,
    ) {}

    /**
     * Format the skill as a SKILL.md file according to the Agent Skills spec.
     */
    public function toSkillMd(): string
    {
        $frontmatter = $this->buildFrontmatter();

        return "---\n{$frontmatter}---\n\n{$this->content}\n";
    }

    /**
     * Build the YAML frontmatter for the SKILL.md file.
     */
    protected function buildFrontmatter(): string
    {
        $lines = [
            "name: {$this->name}",
            "description: {$this->escapeYamlString($this->description)}",
        ];

        if (! empty($this->metadata)) {
            $lines[] = 'metadata:';
            foreach ($this->metadata as $key => $value) {
                $lines[] = "  {$key}: {$this->escapeYamlString((string) $value)}";
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Escape a string for use in YAML.
     */
    protected function escapeYamlString(string $value): string
    {
        // If the string contains special characters, wrap in quotes
        if (preg_match('/[:#\[\]{}|>&*!?]/', $value) || str_contains($value, "\n")) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    /**
     * Get the directory name for this skill.
     */
    public function directoryName(): string
    {
        return $this->name;
    }

    /**
     * Estimate the token count for this skill's listing (name + description).
     *
     * Skills are lazy-loaded, so only the name and description are shown
     * in the agent's skill listing. The full content is loaded on invocation.
     */
    public function estimatedTokens(): int
    {
        $listing = $this->name.' '.$this->description;

        return (int) round(str_word_count($listing) * 1.3);
    }
}
